<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingPaddle;
use App\Models\Court;
use App\Models\Paddle;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All booking rules live here so controllers stay thin:
 * court overlap checks, operating hours, paddle stock, and price calculation.
 *
 * Every write runs in a transaction and locks the court / paddle rows it depends on,
 * so two people cannot grab the same slot or the last paddle at the same moment.
 */
class BookingService
{
    /**
     * Book a court, optionally renting paddles in the same request.
     *
     * @param  array{court_id:int, booking_date:string, start_time:string, end_time:string, notes?:string|null, paddles?:array<int, array{paddle_id:int, quantity:int}>}  $data
     */
    public function create(User $user, array $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            /** @var Court $court */
            $court = Court::whereKey($data['court_id'])->lockForUpdate()->firstOrFail();

            if (! $court->is_active) {
                throw ValidationException::withMessages(['court_id' => 'This court is not available for booking.']);
            }

            $date = Carbon::parse($data['booking_date'])->toDateString();
            $start = self::normalizeTime($data['start_time']);
            $end = self::normalizeTime($data['end_time']);

            $this->assertWithinOperatingHours($court, $start, $end);
            $this->assertNotInPast($date, $start);

            if (Booking::overlapping($court->id, $date, $start, $end)->exists()) {
                throw ValidationException::withMessages(['start_time' => 'This court is already booked for the selected time.']);
            }

            $hours = self::hoursBetween($start, $end);
            $courtFee = round((float) $court->hourly_rate * $hours, 2);

            $booking = Booking::create([
                'user_id' => $user->id,
                'court_id' => $court->id,
                'booking_date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'hours' => $hours,
                'court_fee' => $courtFee,
                'paddle_fee' => 0,
                'total_amount' => $courtFee,
                'status' => BookingStatus::Pending,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['paddles'])) {
                $this->rentPaddles($booking, $data['paddles']);
            }

            return $booking->fresh(['court', 'rentals.paddle']);
        });
    }

    /**
     * Rent paddles for an existing booking (the "add paddles after booking" step).
     * Renting a paddle that is already on the booking increases its quantity.
     *
     * @param  array<int, array{paddle_id:int, quantity:int}>  $items
     */
    public function addPaddles(Booking $booking, array $items): Booking
    {
        return DB::transaction(function () use ($booking, $items) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $this->assertModifiable($booking);

            $this->rentPaddles($booking, $items);

            return $booking->fresh(['court', 'rentals.paddle']);
        });
    }

    /**
     * Remove a paddle rental line from a booking.
     */
    public function removePaddle(Booking $booking, Paddle $paddle): Booking
    {
        return DB::transaction(function () use ($booking, $paddle) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $this->assertModifiable($booking);

            $deleted = BookingPaddle::where('booking_id', $booking->id)
                ->where('paddle_id', $paddle->id)
                ->delete();

            if (! $deleted) {
                throw ValidationException::withMessages(['paddle' => 'This paddle is not rented on this booking.']);
            }

            $booking->recalculateTotals();

            return $booking->fresh(['court', 'rentals.paddle']);
        });
    }

    /**
     * Cancel a booking. This frees the court slot and the rented paddles.
     */
    public function cancel(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $this->assertModifiable($booking);

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $booking->fresh(['court', 'rentals.paddle']);
        });
    }

    /**
     * Admin status change, limited to valid transitions.
     */
    public function changeStatus(Booking $booking, BookingStatus $status): Booking
    {
        $allowed = match ($booking->status) {
            BookingStatus::Pending => [BookingStatus::Confirmed, BookingStatus::Cancelled],
            BookingStatus::Confirmed => [BookingStatus::Completed, BookingStatus::Cancelled],
            default => [],
        };

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "A {$booking->status->value} booking cannot be changed to {$status->value}.",
            ]);
        }

        $booking->update([
            'status' => $status,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : $booking->cancelled_at,
        ]);

        return $booking->fresh(['court', 'user', 'rentals.paddle']);
    }

    /**
     * Hourly slots for a court on a date, each marked available or not.
     *
     * @return array<int, array{start_time:string, end_time:string, available:bool}>
     */
    public function courtSlots(Court $court, string $date): array
    {
        $bookings = Booking::where('court_id', $court->id)
            ->whereDate('booking_date', $date)
            ->whereIn('status', BookingStatus::active())
            ->get(['start_time', 'end_time']);

        $slots = [];
        $cursor = Carbon::parse("$date {$court->opening_time}");
        $closing = Carbon::parse("$date {$court->closing_time}");

        while ($cursor->copy()->addHour()->lte($closing)) {
            $start = $cursor->format('H:i:s');
            $end = $cursor->copy()->addHour()->format('H:i:s');

            $taken = $bookings->contains(fn ($b) => self::normalizeTime($b->start_time) < $end && self::normalizeTime($b->end_time) > $start);

            $slots[] = [
                'start_time' => substr($start, 0, 5),
                'end_time' => substr($end, 0, 5),
                'available' => ! $taken && ! $cursor->isPast(),
            ];

            $cursor->addHour();
        }

        return $slots;
    }

    /**
     * Lock the paddles, check stock for the booking's time slot, and save rental lines.
     *
     * @param  array<int, array{paddle_id:int, quantity:int}>  $items
     */
    protected function rentPaddles(Booking $booking, array $items): void
    {
        // Merge duplicate paddle_ids in the request.
        $requested = collect($items)
            ->groupBy('paddle_id')
            ->map(fn ($group) => (int) $group->sum('quantity'));

        $paddles = Paddle::whereIn('id', $requested->keys())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $date = $booking->booking_date->toDateString();
        $start = self::normalizeTime($booking->start_time);
        $end = self::normalizeTime($booking->end_time);

        foreach ($requested as $paddleId => $quantity) {
            /** @var Paddle|null $paddle */
            $paddle = $paddles->get($paddleId);

            if (! $paddle || ! $paddle->is_active) {
                throw ValidationException::withMessages(['paddles' => "Paddle #{$paddleId} is not available for rent."]);
            }

            // Includes units already on this booking, so adding more only uses free stock.
            $available = $paddle->availableQuantityDuring($date, $start, $end);

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'paddles' => "Only {$available} unit(s) of {$paddle->name} are available for this time slot.",
                ]);
            }

            $line = BookingPaddle::firstOrNew([
                'booking_id' => $booking->id,
                'paddle_id' => $paddle->id,
            ]);

            // Keep the original price if this paddle was already rented on the booking.
            $unitPrice = $line->exists ? (float) $line->unit_price : (float) $paddle->hourly_rate;

            $line->quantity = ($line->quantity ?? 0) + $quantity;
            $line->unit_price = $unitPrice;
            $line->subtotal = round($unitPrice * (float) $booking->hours * $line->quantity, 2);
            $line->save();
        }

        $booking->recalculateTotals();
    }

    protected function assertModifiable(Booking $booking): void
    {
        if (! $booking->isModifiable()) {
            throw ValidationException::withMessages([
                'booking' => 'This booking can no longer be changed.',
            ]);
        }
    }

    protected function assertWithinOperatingHours(Court $court, string $start, string $end): void
    {
        $opening = self::normalizeTime($court->opening_time);
        $closing = self::normalizeTime($court->closing_time);

        if ($start < $opening || $end > $closing) {
            throw ValidationException::withMessages([
                'start_time' => sprintf(
                    '%s is open from %s to %s.',
                    $court->name,
                    substr($opening, 0, 5),
                    substr($closing, 0, 5),
                ),
            ]);
        }
    }

    protected function assertNotInPast(string $date, string $start): void
    {
        if (Carbon::parse("$date $start")->isPast()) {
            throw ValidationException::withMessages(['start_time' => 'You cannot book a time that has already passed.']);
        }
    }

    /** "8:00" / "08:00" / "08:00:00" -> "08:00:00" so string comparison works. */
    public static function normalizeTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }

    public static function hoursBetween(string $start, string $end): float
    {
        return round(Carbon::parse($start)->diffInMinutes(Carbon::parse($end)) / 60, 2);
    }
}
