<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">My bookings</h1>
            <p class="mt-1 text-zinc-600">Your court reservations and paddle rentals.</p>
        </div>
        <a href="{{ route('book') }}" wire:navigate class="btn-accent">+ New booking</a>
    </div>

    <div class="mb-4 inline-flex rounded-xl border border-zinc-200 bg-white p-1" role="tablist">
        @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $key => $label)
            <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="setTab('{{ $key }}')"
                @class([
                    'cursor-pointer rounded-lg px-4 py-2 text-sm font-semibold transition',
                    'bg-court-700 text-white' => $tab === $key,
                    'text-zinc-600 hover:text-zinc-900' => $tab !== $key,
                ])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($bookings as $booking)
            <a href="{{ route('bookings.show', $booking) }}" wire:navigate wire:key="booking-{{ $booking->id }}"
               class="card flex flex-col gap-4 p-5 transition hover:border-court-600 sm:flex-row sm:items-center">
                <div class="flex w-16 shrink-0 flex-col items-center rounded-xl bg-court-50 py-2 text-court-800">
                    <span class="text-[11px] font-semibold uppercase">{{ $booking->booking_date->format('M') }}</span>
                    <span class="text-2xl leading-tight font-bold">{{ $booking->booking_date->format('j') }}</span>
                    <span class="text-[11px]">{{ $booking->booking_date->format('D') }}</span>
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-semibold">{{ $booking->court->name }}</h3>
                        <x-status-badge :status="$booking->status" />
                    </div>
                    <p class="text-sm text-zinc-600">
                        {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
                        · Ref {{ $booking->reference }}
                    </p>
                    @if ($booking->rentals->isNotEmpty())
                        <p class="mt-1 text-xs text-zinc-500">
                            Paddles: {{ $booking->rentals->map(fn ($r) => $r->quantity.' × '.$r->paddle->name)->join(', ') }}
                        </p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold">₱{{ number_format($booking->total_amount, 2) }}</p>
                    <p class="text-xs text-zinc-500">View details →</p>
                </div>
            </a>
        @empty
            <div class="card p-10 text-center">
                <p class="text-zinc-600">No {{ $tab }} bookings.</p>
                @if ($tab === 'upcoming')
                    <a href="{{ route('book') }}" wire:navigate class="btn-primary mt-4">Book a court</a>
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $bookings->links() }}</div>
</div>
