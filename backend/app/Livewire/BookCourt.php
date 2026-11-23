<?php

namespace App\Livewire;

use App\Models\Court;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Book a Court')]
class BookCourt extends Component
{
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
        if (! Auth::check()) {
            session()->put('url.intended', route('book', ['court' => $this->courtId, 'date' => $this->date]));
            session()->flash('success', 'Log in or create an account to finish your booking.');

            return $this->redirectRoute('login', navigate: true);
        }

        // Form-shape checks only; business rules (hours, window, overlap) are enforced by BookingService.
        $this->validate([
            'courtId' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'startTime' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'startTime.required' => 'Please choose a start time.',
        ]);

        // Throws ValidationException (shown on the page) if a rule fails or the slot was taken meanwhile.
        $booking = $bookings->create(Auth::user(), [
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
        $courts = $this->courts();
        $court = $courts->firstWhere('id', $this->courtId);
        $slots = $this->slots($court);
        $index = $this->startIndex($slots);

        return view('livewire.book-court', [
            'courts' => $courts,
            'court' => $court,
            // Not "slots": Livewire 4 reserves $slots in component views for its own slot feature.
            'timeSlots' => $slots,
            'quickDates' => collect(range(0, min(6, $this->maxDaysAhead())))->map(fn ($i) => today()->addDays($i))->all(),
            'maxDate' => today()->addDays($this->maxDaysAhead())->toDateString(),
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

    /**
     * @param  Court|null|false  $court  pass a loaded court to avoid re-querying; false = look it up
     * @return array<int, array{start_time:string, end_time:string, available:bool}>
     */
    private function slots(Court|null|false $court = false): array
    {
        if ($court === false) {
            $court = $this->courtId ? Court::active()->find($this->courtId) : null;
        }

        return $court ? app(BookingService::class)->courtSlots($court, $this->date) : [];
    }

    private function maxDaysAhead(): int
    {
        return (int) config('booking.max_days_ahead');
    }

    private function maxHours(): int
    {
        return (int) config('booking.max_hours');
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
            if (! $slot['available'] || $count >= $this->maxHours()) {
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

        return $parsed->gte(today()) && $parsed->lte(today()->addDays($this->maxDaysAhead()));
    }
}
