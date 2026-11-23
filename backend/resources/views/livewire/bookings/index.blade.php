<div class="page">
    @php($fmt = fn ($t, $f = 'g:i A') => \Carbon\Carbon::parse($t)->format($f))

    <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title text-4xl">My bookings</h1>
            <p class="mt-3 text-navy-700">Open a booking to add paddles or cancel it.</p>
        </div>
        <a href="{{ route('book') }}" wire:navigate class="btn-accent">Book a court</a>
    </header>

    <div class="mb-6 flex gap-6 border-b border-rule" role="tablist" aria-label="Bookings">
        @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $key => $label)
            <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="setTab('{{ $key }}')"
                    @class([
                        '-mb-px cursor-pointer border-b-[3px] pb-2.5 font-semibold transition-colors',
                        'border-brand text-navy-900' => $tab === $key,
                        'border-transparent text-navy-600 hover:text-navy-900' => $tab !== $key,
                    ])>{{ $label }}</button>
        @endforeach
    </div>

    <ul class="space-y-3" role="tabpanel">
        @forelse ($bookings as $booking)
            <li wire:key="booking-{{ $booking->id }}">
                <a href="{{ route('bookings.show', $booking) }}" wire:navigate
                   class="group grid grid-cols-[4.5rem_1fr] items-center overflow-hidden rounded-lg bg-white ring-1 ring-rule hover:ring-navy-300 sm:grid-cols-[4.5rem_1fr_auto]">
                    {{-- Date stub --}}
                    <div class="flex h-full flex-col items-center justify-center bg-navy-900 py-3 text-line">
                        <span class="text-sm opacity-70">{{ $booking->booking_date->format('M') }}</span>
                        <span class="font-condensed text-3xl leading-none font-semibold">{{ $booking->booking_date->format('j') }}</span>
                        <span class="text-sm opacity-70">{{ $booking->booking_date->format('D') }}</span>
                    </div>

                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-lg font-semibold group-hover:underline group-hover:decoration-brand group-hover:underline-offset-4">{{ $booking->court->name }}</p>
                            <x-status-badge :status="$booking->status" />
                        </div>
                        <p class="font-condensed text-lg font-semibold text-navy-700">{{ $fmt($booking->start_time) }} to {{ $fmt($booking->end_time) }}</p>
                        <p class="text-sm text-navy-600">
                            Ref {{ $booking->reference }}@if ($booking->rentals->isNotEmpty()), with {{ $booking->rentals->map(fn ($r) => $r->quantity.' '.Str::plural($r->paddle->name, $r->quantity))->join(', ', ' and ') }}@endif
                        </p>
                    </div>

                    <p class="col-span-2 border-t border-rule px-5 py-3 text-right font-condensed text-2xl font-semibold sm:col-span-1 sm:border-0">
                        ₱{{ number_format($booking->total_amount, 2) }}
                    </p>
                </a>
            </li>
        @empty
            <li class="rounded-lg bg-white px-6 py-12 text-center ring-1 ring-rule">
                @if ($tab === 'upcoming')
                    <p class="text-lg font-semibold">No upcoming games</p>
                    <p class="mt-1 text-navy-600">Book a court and it will show up here.</p>
                    <a href="{{ route('book') }}" wire:navigate class="btn-accent mt-5">Book a court</a>
                @elseif ($tab === 'past')
                    <p class="text-navy-600">Games you've played will show up here.</p>
                @else
                    <p class="text-navy-600">You haven't cancelled any bookings.</p>
                @endif
            </li>
        @endforelse
    </ul>

    <div class="mt-6">{{ $bookings->links() }}</div>
</div>
