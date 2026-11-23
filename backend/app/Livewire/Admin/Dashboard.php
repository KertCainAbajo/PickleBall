<?php

namespace App\Livewire\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingPaddle;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Admin Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $today = today()->toDateString();
        $earning = [BookingStatus::Confirmed->value, BookingStatus::Completed->value];

        return view('livewire.admin.dashboard', [
            'stats' => [
                'today' => Booking::whereDate('booking_date', $today)->whereIn('status', [...BookingStatus::active(), BookingStatus::Completed->value])->count(),
                'pending' => Booking::where('status', BookingStatus::Pending->value)->whereDate('booking_date', '>=', $today)->count(),
                'paddles_today' => (int) BookingPaddle::whereHas('booking', fn ($q) => $q->whereDate('booking_date', $today)->whereIn('status', [...BookingStatus::active(), BookingStatus::Completed->value]))->sum('quantity'),
                'revenue_month' => (float) Booking::whereIn('status', $earning)
                    ->whereBetween('booking_date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])
                    ->sum('total_amount'),
            ],
            'todaysBookings' => Booking::with(['court', 'user', 'rentals.paddle'])
                ->whereDate('booking_date', $today)
                ->where('status', '!=', BookingStatus::Cancelled->value)
                ->orderBy('start_time')
                ->get(),
        ]);
    }
}
