<?php

namespace App\Livewire\Admin;

use App\Enums\BookingStatus;
use App\Livewire\Concerns\RecordsPayments;
use App\Models\Booking;
use App\Models\BookingPaddle;
use App\Models\Court;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Admin Dashboard')]
class Dashboard extends Component
{
    use RecordsPayments;

    /** Days shown in the revenue chart. */
    private const REVENUE_DAYS = 14;

    public ?string $notice = null;

    /** Confirm or cancel a pending booking straight from the queue. */
    public function changeStatus(int $bookingId, string $status, BookingService $bookings): void
    {
        $booking = $bookings->changeStatus(Booking::findOrFail($bookingId), BookingStatus::from($status));

        $this->notice = "Booking {$booking->reference} is now {$booking->status->value}.";
    }

    public function render()
    {
        $today = today();
        $earning = [BookingStatus::Confirmed->value, BookingStatus::Completed->value];
        $notCancelled = [...BookingStatus::active(), BookingStatus::Completed->value];

        $courts = Court::active()->orderBy('name')->get();

        $todaysBookings = Booking::with(['court', 'user', 'rentals'])
            ->onDate($today)
            ->whereIn('status', $notCancelled)
            ->orderBy('start_time')
            ->get();

        // Court-hours the club can sell today vs. hours already booked.
        $openHours = $courts->sum(fn (Court $c) => Carbon::parse($c->opening_time)->diffInMinutes(Carbon::parse($c->closing_time)) / 60);
        $bookedHours = (float) $todaysBookings->sum('hours');

        // Revenue: this month, last month (same day range), and the last 14 days by day.
        $revenueBetween = fn (Carbon $from, Carbon $to) => (float) Booking::whereIn('status', $earning)
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total_amount');

        $monthRevenue = $revenueBetween($today->copy()->startOfMonth(), $today->copy()->endOfMonth());
        $lastMonthToDate = $revenueBetween(
            $today->copy()->subMonthNoOverflow()->startOfMonth(),
            $today->copy()->subMonthNoOverflow(),
        );

        $from = $today->copy()->subDays(self::REVENUE_DAYS - 1);
        $byDay = Booking::whereIn('status', $earning)
            ->whereBetween('booking_date', [$from->toDateString(), $today->toDateString()])
            ->selectRaw('booking_date, SUM(total_amount) as total')
            ->groupBy('booking_date')
            ->pluck('total', 'booking_date');

        $revenueDays = collect(range(0, self::REVENUE_DAYS - 1))->map(function (int $i) use ($from, $byDay) {
            $day = $from->copy()->addDays($i);

            return ['date' => $day, 'total' => (float) ($byDay[$day->toDateString()] ?? 0)];
        });

        // Most-rented paddles this month (units), top 5.
        $topPaddles = BookingPaddle::query()
            ->join('bookings', 'bookings.id', '=', 'booking_paddle.booking_id')
            ->join('paddles', 'paddles.id', '=', 'booking_paddle.paddle_id')
            ->whereIn('bookings.status', $notCancelled)
            ->whereBetween('bookings.booking_date', [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()])
            ->groupBy('paddles.id', 'paddles.name', 'paddles.brand')
            ->selectRaw('paddles.name, paddles.brand, SUM(booking_paddle.quantity) as units')
            ->orderByDesc('units')
            ->limit(5)
            ->get();

        return view('livewire.admin.dashboard', [
            'stats' => [
                'today' => $todaysBookings->count(),
                'booked_hours' => $bookedHours,
                'utilization' => $openHours > 0 ? round($bookedHours / $openHours * 100) : 0,
                'open_hours' => $openHours,
                'paddles_today' => (int) $todaysBookings->sum(fn (Booking $b) => $b->rentals->sum('quantity')),
                'pending' => Booking::where('status', BookingStatus::Pending->value)->where('booking_date', '>=', $today->toDateString())->count(),
                'revenue_month' => $monthRevenue,
                'revenue_change' => $lastMonthToDate > 0 ? round(($monthRevenue - $lastMonthToDate) / $lastMonthToDate * 100) : null,
            ],
            'courts' => $courts,
            'todaysBookings' => $todaysBookings->groupBy('court_id'),
            'dayStart' => $courts->min('opening_time'),
            'dayEnd' => $courts->max('closing_time'),
            'pendingQueue' => Booking::with(['court', 'user'])
                ->where('status', BookingStatus::Pending->value)
                ->where('booking_date', '>=', $today->toDateString())
                ->orderBy('booking_date')->orderBy('start_time')
                ->limit(6)
                ->get(),
            'revenueDays' => $revenueDays,
            'topPaddles' => $topPaddles,
        ]);
    }
}
