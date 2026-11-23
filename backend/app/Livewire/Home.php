<?php

namespace App\Livewire;

use App\Models\Court;
use App\Models\Paddle;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Book a Pickleball Court')]
class Home extends Component
{
    public function render(BookingService $bookings)
    {
        $today = today()->toDateString();
        $courts = Court::active()->orderBy('name')->get();

        // Today's status per court: next free hour and how many free hours remain.
        $courtStatus = $courts->map(function (Court $court) use ($bookings, $today) {
            $free = collect($bookings->courtSlots($court, $today))->where('available', true)->values();
            $closed = Carbon::parse("$today {$court->closing_time}")->isPast();

            return [
                'court' => $court,
                'next_free' => $free->first()['start_time'] ?? null,
                'free_hours' => $free->count(),
                'closed' => $closed,
            ];
        });

        return view('livewire.home', [
            'courtStatus' => $courtStatus,
            'paddles' => Paddle::active()->orderBy('hourly_rate')->get(),
            'opens' => $courts->min('opening_time'),
            'closes' => $courts->max('closing_time'),
        ]);
    }
}
