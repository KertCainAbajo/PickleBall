<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Paddle;
use App\Services\BookingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Step 2 after booking a court: optionally rent paddles for the same time slot.
 */
#[Title('Rent Paddles')]
class RentPaddles extends Component
{
    public Booking $booking;

    /** @var array<int, int> paddle_id => quantity to add */
    public array $quantities = [];

    public function mount(Booking $booking)
    {
        Gate::authorize('update', $booking);

        if (! $booking->isModifiable()) {
            session()->flash('error', 'Paddles can no longer be added to this booking.');

            return $this->redirectRoute('bookings.show', $booking, navigate: true);
        }

        $this->booking = $booking->load(['court', 'rentals.paddle']);
        $this->quantities = $this->paddles->mapWithKeys(fn ($p) => [$p->id => 0])->all();
    }

    /** Active paddles with how many more units are free for this booking's time slot. */
    #[Computed]
    public function paddles(): Collection
    {
        $date = $this->booking->booking_date->toDateString();
        $start = BookingService::normalizeTime($this->booking->start_time);
        $end = BookingService::normalizeTime($this->booking->end_time);

        return Paddle::active()->orderBy('hourly_rate')->get()->each(
            fn (Paddle $paddle) => $paddle->setAttribute('available_quantity', $paddle->availableQuantityDuring($date, $start, $end)),
        );
    }

    #[Computed]
    public function paddleTotal(): float
    {
        return $this->paddles->sum(
            fn (Paddle $p) => (float) $p->hourly_rate * (float) $this->booking->hours * ($this->quantities[$p->id] ?? 0)
        );
    }

    public function increment(int $paddleId): void
    {
        $paddle = $this->paddles->firstWhere('id', $paddleId);

        if ($paddle && ($this->quantities[$paddleId] ?? 0) < $paddle->available_quantity) {
            $this->quantities[$paddleId] = ($this->quantities[$paddleId] ?? 0) + 1;
        }
    }

    public function decrement(int $paddleId): void
    {
        $this->quantities[$paddleId] = max(0, ($this->quantities[$paddleId] ?? 0) - 1);
    }

    public function save(BookingService $bookings)
    {
        $items = collect($this->quantities)
            ->filter(fn ($qty) => (int) $qty > 0)
            ->map(fn ($qty, $id) => ['paddle_id' => (int) $id, 'quantity' => (int) $qty])
            ->values()
            ->all();

        if (empty($items)) {
            return $this->skip();
        }

        // Throws ValidationException (shown on the page) if stock ran out meanwhile.
        $bookings->addPaddles($this->booking, $items);

        session()->flash('success', 'Paddles added to your booking.');

        return $this->redirectRoute('bookings.show', $this->booking, navigate: true);
    }

    public function skip()
    {
        return $this->redirectRoute('bookings.show', $this->booking, navigate: true);
    }

    public function render()
    {
        return view('livewire.rent-paddles');
    }
}
