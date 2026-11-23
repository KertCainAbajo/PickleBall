<div class="page">
    <x-admin-nav title="Dashboard" :subtitle="today()->format('l, F j, Y')" />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Bookings today', number_format($stats['today']), 'admin.bookings', ['date' => today()->toDateString()]],
            ['Awaiting confirmation', number_format($stats['pending']), 'admin.bookings', ['status' => 'pending']],
            ['Paddles rented today', number_format($stats['paddles_today']), null, []],
            ['Revenue this month', '₱'.number_format($stats['revenue_month'], 2), null, []],
        ] as [$label, $value, $route, $params])
            <div class="card p-5">
                <p class="text-sm text-navy-600">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold tracking-tight">{{ $value }}</p>
                @if ($route)
                    <a href="{{ route($route, $params) }}" wire:navigate class="mt-2 inline-block text-xs font-semibold text-navy-700 hover:underline">View</a>
                @endif
            </div>
        @endforeach
    </div>
    <p class="mt-2 text-xs text-navy-600">Revenue counts confirmed and completed bookings.</p>

    <section class="card mt-8 overflow-hidden">
        <div class="border-b border-rule px-5 py-4">
            <h2 class="font-semibold">Today's schedule</h2>
        </div>
        @forelse ($todaysBookings as $booking)
            <a href="{{ route('admin.bookings', ['search' => $booking->reference]) }}" wire:navigate wire:key="today-{{ $booking->id }}"
               class="flex flex-wrap items-center gap-x-6 gap-y-1 border-b border-rule px-5 py-3 text-sm last:border-0 hover:bg-concrete/60">
                <span class="w-36 font-semibold">{{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}</span>
                <span class="w-20">{{ $booking->court->name }}</span>
                <span class="flex-1">{{ $booking->user->name }}
                    @if ($booking->rentals->isNotEmpty())
                        <span class="text-navy-600">· {{ $booking->rentals->sum('quantity') }} paddle(s)</span>
                    @endif
                </span>
                <x-status-badge :status="$booking->status" />
            </a>
        @empty
            <p class="px-5 py-8 text-center text-sm text-navy-600">No bookings today.</p>
        @endforelse
    </section>
</div>
