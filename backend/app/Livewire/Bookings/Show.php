<?php

namespace App\Livewire\Bookings;

use App\Models\Booking;
use App\Models\Paddle;
use App\Services\BookingService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Booking Details')]
class Show extends Component
{
    public Booking $booking;

    public ?string $notice = null;

    public function mount(Booking $booking): void
    {
        Gate::authorize('view', $booking);

        $this->booking = $booking->load(['court', 'rentals.paddle']);
    }

    public function removePaddle(int $paddleId, BookingService $bookings): void
    {
        Gate::authorize('update', $this->booking);

        $this->booking = $bookings->removePaddle($this->booking, Paddle::findOrFail($paddleId));

        $this->notice = 'Paddle removed from your booking.';
    }

    public function cancel(BookingService $bookings): void
    {
        Gate::authorize('update', $this->booking);

        $this->booking = $bookings->cancel($this->booking);

        $this->notice = 'Your booking has been cancelled.';
    }

    public function render()
    {
        return view('livewire.bookings.show');
    }
}
