<?php

namespace App\Livewire\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Admin Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $today = today()->toDateString();

        $todaysBookings = Booking::with(['court', 'user', 'rentals.paddle'])
            ->onDate($today)
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->orderBy('start_time')
            ->get();

        return view('livewire.admin.dashboard', [
            'stats' => [
                // Reuse the already-loaded schedule instead of extra queries.
                'today' => $todaysBookings->count(),
                'paddles_today' => (int) $todaysBookings->sum(fn (Booking $b) => $b->rentals->sum('quantity')),
                'pending' => Booking::where('status', BookingStatus::Pending->value)
                    ->where('booking_date', '>=', $today)
                    ->count(),
                // Revenue = confirmed + completed bookings this month.
                'revenue_month' => (float) Booking::whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Completed->value])
                    ->whereBetween('booking_date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])
                    ->sum('total_amount'),
            ],
            'todaysBookings' => $todaysBookings,
        ]);
    }
}
