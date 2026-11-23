<?php

namespace App\Livewire\Bookings;

use App\Enums\BookingStatus;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('My Bookings')]
class Index extends Component
{
    use WithPagination;

    /** upcoming | past | cancelled */
    #[Url]
    public string $tab = 'upcoming';

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['upcoming', 'past', 'cancelled'], true) ? $tab : 'upcoming';
        $this->resetPage();
    }

    public function render()
    {
        $query = auth()->user()->bookings()->with(['court', 'rentals.paddle']);

        match ($this->tab) {
            'past' => $query->where('status', '!=', BookingStatus::Cancelled->value)
                ->where(fn ($q) => $q->whereDate('booking_date', '<', today())
                    ->orWhere('status', BookingStatus::Completed->value))
                ->orderByDesc('booking_date')->orderByDesc('start_time'),
            'cancelled' => $query->where('status', BookingStatus::Cancelled->value)
                ->orderByDesc('booking_date')->orderByDesc('start_time'),
            default => $query->whereIn('status', BookingStatus::active())
                ->whereDate('booking_date', '>=', today())
                ->orderBy('booking_date')->orderBy('start_time'),
        };

        return view('livewire.bookings.index', [
            'bookings' => $query->paginate(10),
        ]);
    }
}
