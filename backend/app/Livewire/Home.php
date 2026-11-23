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
        $nowTime = now()->format('H:i');
        $courts = Court::active()->orderBy('name')->get();
        $paddles = Paddle::active()->orderBy('hourly_rate')->get();

        // Per court: today's hour-by-hour timeline and a one-line status.
        $courtStatus = $courts->map(function (Court $court) use ($bookings, $today, $nowTime) {
            $hours = collect($bookings->courtSlots($court, $today));
            $free = $hours->where('available', true)->values();
            $current = $hours->first(fn ($h) => $h['start_time'] <= $nowTime && $h['end_time'] > $nowTime);

            return [
                'court' => $court,
                'hours' => $hours->all(),
                'next_free' => $free->first()['start_time'] ?? null,
                'free_hours' => $free->count(),
                'open_now' => $current !== null && ! $current['taken'],
                'closed' => Carbon::parse("$today {$court->closing_time}")->isPast(),
            ];
        });

        return view('livewire.home', [
            'courtStatus' => $courtStatus,
            'paddles' => $paddles,
            'stats' => [
                'open_now' => $courtStatus->where('open_now', true)->count(),
                'courts' => $courts->count(),
                'free_hours' => $courtStatus->sum('free_hours'),
                'paddles' => $paddles->sum('stock_quantity'),
                'from_rate' => $courts->min('hourly_rate'),
            ],
            'opens' => $courts->min('opening_time'),
            'closes' => $courts->max('closing_time'),
        ]);
    }
}
