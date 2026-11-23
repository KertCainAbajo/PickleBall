<?php

namespace App\Livewire;

use App\Models\Court;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Book a Court')]
class BookCourt extends Component
{
    /** How far ahead customers can book. */
    public const DAYS_AHEAD = 30;

    /** Longest single booking, in hours. */
    public const MAX_HOURS = 4;

    #[Url(as: 'court')]
    public ?int $courtId = null;

    #[Url]
    public string $date = '';

    /** Selected start slot, "HH:MM". */
    public ?string $startTime = null;

    public int $duration = 1;

    public string $notes = '';

    public function mount(): void
    {
        if (! $this->isValidDate($this->date)) {
            $this->date = today()->toDateString();
        }

        $courts = $this->courts();

        if (! $courts->contains('id', $this->courtId)) {
            $this->courtId = $courts->first()?->id;
        }
    }

    public function selectCourt(int $id): void
    {
        $this->courtId = $id;
        $this->clearSelection();
    }

    public function selectDate(string $date): void
    {
        $this->date = $date;
        $this->updatedDate();
    }

    public function updatedDate(): void
    {
        if (! $this->isValidDate($this->date)) {
            $this->date = today()->toDateString();
        }
        $this->clearSelection();
    }

    public function selectSlot(string $start): void
    {
        $slots = $this->slots();
        $slot = collect($slots)->firstWhere('start_time', $start);

        if (! $slot || ! $slot['available']) {
            return;
        }

        // Clicking the next free slot right after the selection extends it.
        if ($this->startTime && $start === $this->endTime() && $this->duration < $this->maxDuration($slots)) {
            $this->duration++;

            return;
        }

        $this->startTime = $start;
        $this->duration = 1;
    }

    public function setDuration(int $hours): void
    {
        $this->duration = max(1, min($hours, $this->maxDuration($this->slots())));
    }

    public function book(BookingService $bookings)
    {
        if (! auth()->check()) {
            session()->put('url.intended', route('book', ['court' => $this->courtId, 'date' => $this->date]));
            session()->flash('success', 'Log in or create an account to finish your booking.');

            return $this->redirectRoute('login', navigate: true);
        }

        $this->validate([
            'courtId' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'startTime' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', 'min:1', 'max:'.self::MAX_HOURS],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'startTime.required' => 'Please choose a start time.',
        ]);

        // Throws ValidationException (shown on the page) if the slot was taken in the meantime.
        $booking = $bookings->create(auth()->user(), [
            'court_id' => $this->courtId,
            'booking_date' => $this->date,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime(),
            'notes' => $this->notes ?: null,
        ]);

        session()->flash('success', "Court booked! Reference {$booking->reference}. Want to rent paddles?");

        return $this->redirectRoute('bookings.paddles', $booking, navigate: true);
    }

    public function render()
    {
        // Derived values are computed fresh on every render so they always match the current selection.
        $court = $this->court();
        $slots = $this->slots();
        $index = $this->startIndex($slots);

        return view('livewire.book-court', [
            'courts' => $this->courts(),
            'court' => $court,
            'slots' => $slots,
            'quickDates' => collect(range(0, 6))->map(fn ($i) => today()->addDays($i))->all(),
            'maxDuration' => $this->maxDuration($slots),
            'selectedStarts' => $index === null ? [] : array_column(array_slice($slots, $index, $this->duration), 'start_time'),
            'endTime' => $this->endTime(),
            'total' => $court && $this->startTime ? (float) $court->hourly_rate * $this->duration : 0.0,
        ]);
    }

    private function courts(): Collection
    {
        return Court::active()->orderBy('name')->get();
    }

    private function court(): ?Court
    {
        return $this->courtId ? Court::active()->find($this->courtId) : null;
    }

    /** @return array<int, array{start_time:string, end_time:string, available:bool}> */
    private function slots(): array
    {
        $court = $this->court();

        return $court ? app(BookingService::class)->courtSlots($court, $this->date) : [];
    }

    /** How many hours can be booked from the selected start before a taken slot, closing, or the max. */
    private function maxDuration(array $slots): int
    {
        $index = $this->startIndex($slots);

        if ($index === null) {
            return 0;
        }

        $count = 0;
        foreach (array_slice($slots, $index) as $slot) {
            if (! $slot['available'] || $count >= self::MAX_HOURS) {
                break;
            }
            $count++;
        }

        return $count;
    }

    private function endTime(): ?string
    {
        return $this->startTime
            ? Carbon::parse($this->startTime)->addHours($this->duration)->format('H:i')
            : null;
    }

    private function startIndex(array $slots): ?int
    {
        if (! $this->startTime) {
            return null;
        }

        $index = array_search($this->startTime, array_column($slots, 'start_time'), true);

        return $index === false ? null : $index;
    }

    private function clearSelection(): void
    {
        $this->startTime = null;
        $this->duration = 1;
        $this->resetErrorBag();
    }

    private function isValidDate(?string $date): bool
    {
        if (! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $parsed = Carbon::parse($date);

        return $parsed->gte(today()) && $parsed->lte(today()->addDays(self::DAYS_AHEAD));
    }
}
