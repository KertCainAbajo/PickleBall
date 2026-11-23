<?php

namespace App\Livewire\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('My Bookings')]
class Index extends Component
{
    use WithPagination;

    public const TABS = ['upcoming', 'past', 'cancelled'];

    #[Url]
    public string $tab = 'upcoming';

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'upcoming';
        $this->resetPage();
    }

    public function render()
    {
        $query = Booking::query()
            ->where('user_id', Auth::id())
            ->with(['court', 'rentals.paddle']);

        match ($this->tab) {
            'past' => $query->past()->orderByDesc('booking_date')->orderByDesc('start_time'),
            'cancelled' => $query->where('status', BookingStatus::Cancelled->value)->orderByDesc('booking_date')->orderByDesc('start_time'),
            default => $query->upcoming()->orderBy('booking_date')->orderBy('start_time'),
        };

        return view('livewire.bookings.index', [
            'bookings' => $query->paginate(10),
        ]);
    }
}
