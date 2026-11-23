<?php

namespace App\Livewire\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Services\BookingService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Manage Bookings')]
class Bookings extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $date = '';

    #[Url]
    public string $court = '';

    #[Url]
    public string $search = '';

    public ?string $notice = null;

    public function updating($property): void
    {
        if (in_array($property, ['status', 'date', 'court', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('status', 'date', 'court', 'search');
        $this->resetPage();
    }

    public function changeStatus(int $bookingId, string $status, BookingService $bookings): void
    {
        $booking = Booking::findOrFail($bookingId);
        $booking = $bookings->changeStatus($booking, BookingStatus::from($status));

        $this->notice = "Booking {$booking->reference} is now {$booking->status->value}.";
    }

    public function render()
    {
        $bookings = Booking::query()
            ->with(['court', 'user', 'rentals.paddle'])
            ->when(BookingStatus::tryFrom($this->status), fn ($q, $s) => $q->where('status', $s->value))
            ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date), fn ($q) => $q->whereDate('booking_date', $this->date))
            ->when(ctype_digit($this->court), fn ($q) => $q->where('court_id', (int) $this->court))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($q) => $q->where('reference', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->orderByDesc('booking_date')
            ->orderBy('start_time')
            ->paginate(15);

        return view('livewire.admin.bookings', [
            'bookings' => $bookings,
            'courts' => Court::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
