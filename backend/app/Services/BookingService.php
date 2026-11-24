<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\BookingPaddle;
use App\Models\Court;
use App\Models\Paddle;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All booking rules live here so every screen behaves the same:
 * court overlap, operating hours, booking window, duration, paddle stock and pricing.
 *
 * Writes run in a transaction and lock the rows they depend on (court, booking, paddles),
 * so two people cannot take the same slot or the last paddle at the same moment.
 * Lock order is always court/booking first, then paddles, to avoid deadlocks.
 */
class BookingService
{
    /** Length of one bookable slot on the availability grid, in minutes. */
    public const SLOT_MINUTES = 60;

    /*
    |--------------------------------------------------------------------------
    | Customer actions
    |--------------------------------------------------------------------------
    */

    /**
     * Book a court, optionally renting paddles in the same step.
     *
     * @param  array{court_id:int, booking_date:string, start_time:string, end_time:string, notes?:string|null, paddles?:array<int, array{paddle_id:int, quantity:int}>}  $data
     */
    public function create(User $user, array $data): Booking
    {
        $date = Carbon::parse($data['booking_date'])->toDateString();
        $start = self::normalizeTime($data['start_time']);
        $end = self::normalizeTime($data['end_time']);
        $paddleItems = $this->normalizePaddleItems($data['paddles'] ?? []);

        // Checks that don't need the database run before opening a transaction.
        $this->assertValidTimeRange($start, $end);
        $this->assertWithinBookingWindow($date, $start);

        return DB::transaction(function () use ($user, $data, $date, $start, $end, $paddleItems) {
            /** @var Court|null $court */
            $court = Court::whereKey($data['court_id'])->lockForUpdate()->first();

            if (! $court || ! $court->is_active) {
                throw ValidationException::withMessages(['court_id' => 'This court is not available for booking.']);
            }

            $this->assertWithinOperatingHours($court, $start, $end);

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

            if ($paddleItems->isNotEmpty()) {
                $this->rentPaddles($booking, $paddleItems);
            }

            return $booking->load(['court', 'rentals.paddle']);
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
        $paddleItems = $this->normalizePaddleItems($items);

        if ($paddleItems->isEmpty()) {
            throw ValidationException::withMessages(['paddles' => 'Choose at least one paddle.']);
        }

        return DB::transaction(function () use ($booking, $paddleItems) {
            $booking = $this->lockModifiable($booking);

            $this->rentPaddles($booking, $paddleItems);

            return $booking->load(['court', 'rentals.paddle']);
        });
    }

    /** Remove a paddle rental line from a booking. */
    public function removePaddle(Booking $booking, Paddle $paddle): Booking
    {
        return DB::transaction(function () use ($booking, $paddle) {
            $booking = $this->lockModifiable($booking);

            if (! $booking->rentals()->where('paddle_id', $paddle->id)->delete()) {
                throw ValidationException::withMessages(['paddle' => 'This paddle is not rented on this booking.']);
            }

            $booking->recalculateTotals();

            return $booking->load(['court', 'rentals.paddle']);
        });
    }

    /** Cancel a booking. This frees the court slot and the rented paddles. */
    public function cancel(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking = $this->lockModifiable($booking);

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $booking->load(['court', 'rentals.paddle']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Admin actions
    |--------------------------------------------------------------------------
    */

    /** Change a booking's status, limited to valid transitions. */
    public function changeStatus(Booking $booking, BookingStatus $status): Booking
    {
        return DB::transaction(function () use ($booking, $status) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

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

            // The customer pays first; only then is the court confirmed (and later completed).
            if (in_array($status, [BookingStatus::Confirmed, BookingStatus::Completed], true) && ! $booking->isPaid()) {
                throw ValidationException::withMessages([
                    'status' => sprintf('Booking %s has ₱%s unpaid. Record the payment before marking it %s.',
                        $booking->reference, number_format($booking->balanceDue(), 2), $status->value),
                ]);
            }

            $booking->update([
                'status' => $status,
                'cancelled_at' => $status === BookingStatus::Cancelled ? now() : $booking->cancelled_at,
            ]);

            return $booking->load(['court', 'user', 'rentals.paddle']);
        });
    }

    /**
     * Record that the customer paid the full amount still owed on the booking.
     * GCash and bank transfers need the transaction reference so the payment can be traced.
     */
    public function recordPayment(Booking $booking, PaymentMethod $method, ?string $reference = null): Booking
    {
        $reference = trim((string) $reference) ?: null;

        if ($method->needsReference() && $reference === null) {
            throw ValidationException::withMessages(['payment_reference' => "Enter the {$method->label()} reference number."]);
        }

        return DB::transaction(function () use ($booking, $method, $reference) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $booking->status->isActive()) {
                throw ValidationException::withMessages(['payment' => "A {$booking->status->value} booking cannot take a payment."]);
            }

            if ($booking->isPaid()) {
                throw ValidationException::withMessages(['payment' => "Booking {$booking->reference} is already paid."]);
            }

            $booking->update([
                'amount_paid' => $booking->total_amount,
                'payment_method' => $method,
                'payment_reference' => $reference,
                'paid_at' => now(),
            ]);

            return $booking->load(['court', 'user', 'rentals.paddle']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Availability (read-only)
    |--------------------------------------------------------------------------
    */

    /**
     * Slots for a court on a date, each marked available or not. One query.
     *
     * `available` = can be booked now. `taken` = held by a booking. `past` = already started.
     *
     * @return array<int, array{start_time:string, end_time:string, available:bool, taken:bool, past:bool}>
     */
    public function courtSlots(Court $court, Carbon|string $date): array
    {
        $date = Carbon::parse($date)->toDateString();

        $taken = Booking::where('court_id', $court->id)
            ->onDate($date)
            ->active()
            ->get(['start_time', 'end_time'])
            ->map(fn (Booking $b) => [self::normalizeTime($b->start_time), self::normalizeTime($b->end_time)]);

        $slots = [];
        $cursor = Carbon::parse("$date {$court->opening_time}");
        $closing = Carbon::parse("$date {$court->closing_time}");
        $now = now();

        while ($cursor->copy()->addMinutes(self::SLOT_MINUTES)->lte($closing)) {
            $next = $cursor->copy()->addMinutes(self::SLOT_MINUTES);
            $start = $cursor->format('H:i:s');
            $end = $next->format('H:i:s');

            $isTaken = $taken->contains(fn (array $range) => $range[0] < $end && $range[1] > $start);
            $isPast = $cursor->lte($now);

            $slots[] = [
                'start_time' => substr($start, 0, 5),
                'end_time' => substr($end, 0, 5),
                'available' => ! $isTaken && ! $isPast,
                'taken' => $isTaken,
                'past' => $isPast,
            ];

            $cursor = $next;
        }

        return $slots;
    }

    /**
     * Active paddles, each with an `available_quantity` attribute for the given slot. Two queries total.
     *
     * @return Collection<int, Paddle>
     */
    public function paddleAvailability(Carbon|string $date, string $start, string $end): Collection
    {
        $start = self::normalizeTime($start);
        $end = self::normalizeTime($end);
        $rented = Paddle::rentedQuantities($date, $start, $end);

        return Paddle::active()->orderBy('hourly_rate')->get()->each(
            fn (Paddle $paddle) => $paddle->setAttribute(
                'available_quantity',
                max(0, $paddle->stock_quantity - $rented->get($paddle->id, 0)),
            ),
        );
    }

    /** Same as paddleAvailability(), for an existing booking's time slot. */
    public function paddleAvailabilityFor(Booking $booking): Collection
    {
        return $this->paddleAvailability($booking->booking_date, $booking->start_time, $booking->end_time);
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /**
     * Lock the paddles, check stock for the booking's slot, and save rental lines.
     *
     * @param  Collection<int, int>  $items  paddle_id => quantity to add
     */
    protected function rentPaddles(Booking $booking, Collection $items): void
    {
        $paddles = Paddle::whereIn('id', $items->keys())->lockForUpdate()->get()->keyBy('id');

        // One query for current usage of all requested paddles (includes this booking's own lines).
        $rented = Paddle::rentedQuantities(
            $booking->booking_date,
            self::normalizeTime($booking->start_time),
            self::normalizeTime($booking->end_time),
            $items->keys()->all(),
        );

        $existingLines = $booking->rentals()->whereIn('paddle_id', $items->keys())->get()->keyBy('paddle_id');
        $maxPerType = (int) config('booking.max_paddles_per_type');

        foreach ($items as $paddleId => $quantity) {
            /** @var Paddle|null $paddle */
            $paddle = $paddles->get($paddleId);

            if (! $paddle || ! $paddle->is_active) {
                throw ValidationException::withMessages(['paddles' => "Paddle #{$paddleId} is not available for rent."]);
            }

            $available = max(0, $paddle->stock_quantity - $rented->get($paddleId, 0));

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'paddles' => "Only {$available} {$paddle->name} paddle(s) are available for this time slot.",
                ]);
            }

            /** @var BookingPaddle $line */
            $line = $existingLines->get($paddleId) ?? new BookingPaddle(['booking_id' => $booking->id, 'paddle_id' => $paddleId]);
            $newQuantity = ($line->quantity ?? 0) + $quantity;

            if ($newQuantity > $maxPerType) {
                throw ValidationException::withMessages([
                    'paddles' => "You can rent at most {$maxPerType} {$paddle->name} paddles per booking.",
                ]);
            }

            // Keep the original price if this paddle was already rented on the booking.
            $unitPrice = $line->exists ? (float) $line->unit_price : (float) $paddle->hourly_rate;

            $line->fill([
                'quantity' => $newQuantity,
                'unit_price' => $unitPrice,
                'subtotal' => round($unitPrice * (float) $booking->hours * $newQuantity, 2),
            ])->save();
        }

        $booking->recalculateTotals();
    }

    /**
     * Merge duplicate paddle_ids and validate quantities.
     *
     * @param  array<int, array{paddle_id:mixed, quantity:mixed}>  $items
     * @return Collection<int, int> paddle_id => quantity
     */
    protected function normalizePaddleItems(array $items): Collection
    {
        $maxPerType = (int) config('booking.max_paddles_per_type');

        return collect($items)
            ->each(function ($item) use ($maxPerType) {
                $id = $item['paddle_id'] ?? null;
                $qty = $item['quantity'] ?? null;

                if (! is_numeric($id) || ! is_numeric($qty) || (int) $qty != $qty || $qty < 1 || $qty > $maxPerType) {
                    throw ValidationException::withMessages([
                        'paddles' => "Each paddle needs a quantity between 1 and {$maxPerType}.",
                    ]);
                }
            })
            ->groupBy(fn ($item) => (int) $item['paddle_id'])
            ->map(fn (Collection $group) => (int) $group->sum('quantity'));
    }

    /** Re-read the booking with a row lock and make sure it can still be changed. */
    protected function lockModifiable(Booking $booking): Booking
    {
        $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

        if (! $booking->isModifiable()) {
            throw ValidationException::withMessages(['booking' => 'This booking can no longer be changed.']);
        }

        return $booking;
    }

    protected function assertValidTimeRange(string $start, string $end): void
    {
        $allowedMinutes = config('booking.allowed_minutes');

        foreach (['start_time' => $start, 'end_time' => $end] as $field => $time) {
            if (! in_array((int) substr($time, 3, 2), $allowedMinutes, true) || substr($time, 6, 2) !== '00') {
                $marks = collect($allowedMinutes)->map(fn ($m) => sprintf(':%02d', $m))->join(' or ');
                throw ValidationException::withMessages([$field => "Times must be on {$marks} (e.g. 08:00)."]);
            }
        }

        if ($end <= $start) {
            throw ValidationException::withMessages(['end_time' => 'End time must be after the start time.']);
        }

        $hours = self::hoursBetween($start, $end);
        $min = config('booking.min_hours');
        $max = config('booking.max_hours');

        if ($hours < $min || $hours > $max) {
            throw ValidationException::withMessages([
                'end_time' => "A booking must be between {$min} and {$max} hours long.",
            ]);
        }
    }

    protected function assertWithinBookingWindow(string $date, string $start): void
    {
        if (Carbon::parse("$date $start")->lte(now())) {
            throw ValidationException::withMessages(['start_time' => 'You cannot book a time that has already passed.']);
        }

        $maxDays = (int) config('booking.max_days_ahead');

        if (Carbon::parse($date)->gt(today()->addDays($maxDays))) {
            throw ValidationException::withMessages([
                'booking_date' => "Bookings can be made up to {$maxDays} days ahead.",
            ]);
        }
    }

    protected function assertWithinOperatingHours(Court $court, string $start, string $end): void
    {
        $opening = self::normalizeTime($court->opening_time);
        $closing = self::normalizeTime($court->closing_time);

        if ($start < $opening || $end > $closing) {
            throw ValidationException::withMessages([
                'start_time' => sprintf('%s is open from %s to %s.', $court->name, substr($opening, 0, 5), substr($closing, 0, 5)),
            ]);
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
