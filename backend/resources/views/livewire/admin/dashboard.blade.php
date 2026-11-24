<div class="page">
    @php
        $fmt = fn ($t, $f = 'g:i A') => \Carbon\Carbon::parse($t)->format($f);
        $peso = fn ($n, $d = 0) => '₱'.number_format($n, $d);
        $minutes = fn ($t) => (int) \Carbon\Carbon::parse($t)->format('G') * 60 + (int) \Carbon\Carbon::parse($t)->format('i');

        // Timeline geometry (minutes from the earliest opening to the latest closing).
        $tlStart = $dayStart ? $minutes($dayStart) : 360;
        $tlSpan = max(60, ($dayEnd ? $minutes($dayEnd) : 1320) - $tlStart);
        $nowMin = $minutes(now());
        $nowPct = ($nowMin - $tlStart) / $tlSpan * 100;

        $revMax = max(1, $revenueDays->max('total'));
        $revTotal = $revenueDays->sum('total');
        $paddleMax = max(1, (int) $topPaddles->max('units'));
    @endphp

    <x-admin-nav title="Dashboard" :subtitle="today()->format('l, F j, Y')">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.bookings', ['status' => 'pending']) }}" wire:navigate class="btn-secondary btn-sm">Pending bookings</a>
            <a href="{{ route('admin.bookings', ['date' => today()->toDateString()]) }}" wire:navigate class="btn-primary btn-sm">Today's bookings</a>
        </div>
    </x-admin-nav>

    @if ($notice)
        <p class="mb-5 rounded-md border-l-4 border-ball-dark bg-white px-4 py-3 font-medium shadow-lift" role="status">{{ $notice }}</p>
    @endif

    {{-- ── KPI tiles ─────────────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-white p-5 ring-1 ring-rule">
            <p class="text-sm font-medium text-navy-600">Bookings today</p>
            <p class="mt-2 scoreboard-num text-5xl">{{ $stats['today'] }}</p>
            <p class="mt-2 text-sm text-navy-600">{{ rtrim(rtrim(number_format($stats['booked_hours'], 1), '0'), '.') }} court {{ Str::plural('hour', $stats['booked_hours']) }}, {{ $stats['paddles_today'] }} {{ Str::plural('paddle', $stats['paddles_today']) }} rented</p>
        </div>

        <div @class(['rounded-xl bg-white p-5 ring-1', 'ring-amber-300' => $stats['pending'] > 0, 'ring-rule' => $stats['pending'] === 0])>
            <p class="flex items-center justify-between text-sm font-medium text-navy-600">
                Awaiting confirmation
                @if ($stats['pending'] > 0)
                    <span class="badge bg-amber-100 whitespace-nowrap text-amber-800">Needs action</span>
                @endif
            </p>
            <p class="mt-2 scoreboard-num text-5xl">{{ $stats['pending'] }}</p>
            <a href="#queue" class="mt-2 inline-block text-sm font-semibold text-navy-800 underline decoration-brand decoration-2 underline-offset-4">Review the queue</a>
        </div>

        <div class="rounded-xl bg-white p-5 ring-1 ring-rule">
            <p class="text-sm font-medium text-navy-600">Court time booked today</p>
            <p class="mt-2 scoreboard-num text-5xl">{{ $stats['utilization'] }}<span class="text-2xl text-navy-600">%</span></p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-navy-100" role="img" aria-label="{{ $stats['utilization'] }}% of court time booked">
                <div class="h-full rounded-full bg-navy-700" @style(['width: '.min(100, $stats['utilization']).'%'])></div>
            </div>
            <p class="mt-2 text-sm text-navy-600">{{ rtrim(rtrim(number_format($stats['booked_hours'], 1), '0'), '.') }} of {{ rtrim(rtrim(number_format($stats['open_hours'], 1), '0'), '.') }} hours across all courts</p>
        </div>

        <div class="rounded-xl bg-white p-5 ring-1 ring-rule">
            <p class="text-sm font-medium text-navy-600">Revenue this month</p>
            <p class="mt-2 scoreboard-num text-5xl">{{ $peso($stats['revenue_month']) }}</p>
            <p class="mt-2 flex items-center gap-1.5 text-sm text-navy-600">
                @if ($stats['revenue_change'] === null)
                    Confirmed and completed bookings
                @else
                    <svg viewBox="0 0 16 16" @class(['size-4', 'rotate-180' => $stats['revenue_change'] < 0]) fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 13V3M4 7l4-4 4 4"/></svg>
                    <span class="font-semibold text-navy-900">{{ $stats['revenue_change'] > 0 ? '+' : '' }}{{ $stats['revenue_change'] }}%</span> vs. same days last month
                @endif
            </p>
        </div>
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- ── Today's court timeline ─────────────────────────────── --}}
        <section class="min-w-0 rounded-xl bg-white p-5 ring-1 ring-rule" aria-labelledby="timeline-title">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h2 id="timeline-title" class="text-lg font-bold">Today on court</h2>
                <ul class="flex flex-wrap gap-4 text-sm text-navy-700" aria-label="Key">
                    <li class="flex items-center gap-1.5"><span class="h-3 w-4 rounded-sm bg-emerald-200 ring-1 ring-emerald-600/40"></span>Confirmed</li>
                    <li class="flex items-center gap-1.5"><span class="hatched h-3 w-4 rounded-sm bg-amber-100 ring-1 ring-amber-500/50"></span>Pending</li>
                    <li class="flex items-center gap-1.5"><span class="h-3 w-4 rounded-sm bg-sky-100 ring-1 ring-sky-500/40"></span>Completed</li>
                </ul>
            </div>

            @if ($courts->isEmpty())
                <p class="mt-6 text-navy-600">No active courts.</p>
            @else
                <div class="mt-5 overflow-x-auto">
                    <div class="min-w-[44rem]">
                        {{-- hour labels --}}
                        <div class="relative ml-28 h-5 text-xs text-navy-600">
                            @for ($m = $tlStart; $m <= $tlStart + $tlSpan; $m += 120)
                                <span class="absolute -translate-x-1/2" @style(['left: '.(($m - $tlStart) / $tlSpan * 100).'%'])>{{ \Carbon\Carbon::today()->addMinutes($m)->format('ga') }}</span>
                            @endfor
                        </div>

                        <div class="relative mt-1 space-y-2">
                            @foreach ($courts as $court)
                                <div class="flex items-center gap-3" wire:key="tl-{{ $court->id }}">
                                    <p class="w-25 shrink-0 truncate text-sm font-semibold">{{ $court->name }}</p>
                                    <div class="relative h-12 flex-1 rounded-lg bg-concrete">
                                        {{-- hour grid --}}
                                        @for ($m = $tlStart + 60; $m < $tlStart + $tlSpan; $m += 60)
                                            <span class="absolute inset-y-0 w-px bg-white" @style(['left: '.(($m - $tlStart) / $tlSpan * 100).'%']) aria-hidden="true"></span>
                                        @endfor

                                        @foreach ($todaysBookings->get($court->id, collect()) as $b)
                                            @php
                                                $left = ($minutes($b->start_time) - $tlStart) / $tlSpan * 100;
                                                $width = (float) $b->hours * 60 / $tlSpan * 100;
                                            @endphp
                                            <a href="{{ route('admin.bookings', ['search' => $b->reference]) }}" wire:navigate
                                               title="{{ $b->user->name }}, {{ $fmt($b->start_time) }} to {{ $fmt($b->end_time) }} ({{ $b->status->value }})"
                                               @style(['left: calc('.$left.'% + 1px)', 'width: calc('.$width.'% - 2px)'])
                                               @class([
                                                   'absolute inset-y-1 flex flex-col justify-center overflow-hidden rounded-md px-2 text-xs leading-tight ring-1 transition-shadow hover:shadow-lift',
                                                   'bg-emerald-200 text-emerald-950 ring-emerald-600/40' => $b->status === \App\Enums\BookingStatus::Confirmed,
                                                   'hatched bg-amber-100 text-amber-950 ring-amber-500/50' => $b->status === \App\Enums\BookingStatus::Pending,
                                                   'bg-sky-100 text-sky-950 ring-sky-500/40' => $b->status === \App\Enums\BookingStatus::Completed,
                                               ])>
                                                <span class="truncate font-semibold">{{ $b->user->name }}</span>
                                                <span class="truncate">{{ $fmt($b->start_time, 'g:i') }}–{{ $fmt($b->end_time, 'g:i A') }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            {{-- "now" line --}}
                            @if ($nowPct > 0 && $nowPct < 100)
                                <div class="pointer-events-none absolute inset-y-0 right-0 left-28" aria-hidden="true">
                                    <div class="absolute inset-y-0 w-0.5 bg-brand" @style(['left: '.$nowPct.'%'])>
                                        <span class="absolute -top-1.5 -left-[3px] size-2 rounded-full bg-brand"></span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @if ($todaysBookings->isEmpty())
                    <p class="mt-4 text-sm text-navy-600">No bookings yet today. The orange line shows the current time.</p>
                @endif
            @endif
        </section>

        {{-- ── Confirmation queue ─────────────────────────────────── --}}
        <section id="queue" class="scroll-mt-24 rounded-xl bg-white ring-1 ring-rule" aria-labelledby="queue-title">
            <div class="flex items-baseline justify-between border-b border-rule px-5 py-4">
                <h2 id="queue-title" class="text-lg font-bold">Needs confirmation</h2>
                <span class="text-sm text-navy-600">{{ $stats['pending'] }} pending</span>
            </div>
            <ul class="divide-y divide-rule">
                @forelse ($pendingQueue as $b)
                    <li wire:key="q-{{ $b->id }}" class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $b->user->name }}</p>
                                <p class="text-sm text-navy-600">{{ $b->court->name }}, {{ $b->booking_date->isToday() ? 'today' : $b->booking_date->format('D, M j') }}</p>
                                <p class="font-condensed font-bold">{{ $fmt($b->start_time) }}–{{ $fmt($b->end_time) }}</p>
                            </div>
                            <p class="font-condensed text-lg font-bold whitespace-nowrap">{{ $peso($b->total_amount) }}</p>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button type="button" wire:click="changeStatus({{ $b->id }}, 'confirmed')" class="btn-primary btn-sm flex-1">Confirm</button>
                            <button type="button" wire:click="changeStatus({{ $b->id }}, 'cancelled')" wire:confirm="Cancel booking {{ $b->reference }}?"
                                    class="btn-secondary btn-sm text-red-700">Cancel</button>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center">
                        <span class="mx-auto mb-2 grid size-10 place-items-center rounded-full bg-ball text-navy-900" aria-hidden="true">
                            <svg viewBox="0 0 16 16" class="size-5" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 8.5 6.5 12 13 4.5"/></svg>
                        </span>
                        <p class="font-semibold">All caught up</p>
                        <p class="text-sm text-navy-600">No bookings are waiting for confirmation.</p>
                    </li>
                @endforelse
            </ul>
            @if ($stats['pending'] > $pendingQueue->count())
                <a href="{{ route('admin.bookings', ['status' => 'pending']) }}" wire:navigate class="block border-t border-rule px-5 py-3 text-center text-sm font-semibold text-navy-800 hover:bg-concrete/60">See all {{ $stats['pending'] }} pending</a>
            @endif
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        {{-- ── Revenue, last 14 days (single series: one hue, no legend) ── --}}
        <section class="rounded-xl bg-white p-5 ring-1 ring-rule" aria-labelledby="revenue-title">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="revenue-title" class="text-lg font-bold">Revenue, last 14 days</h2>
                <p class="text-sm text-navy-600"><span class="font-condensed text-lg font-bold text-navy-900">{{ $peso($revTotal) }}</span> total</p>
            </div>

            <div class="mt-5 flex gap-3">
                {{-- y-axis: max and zero only, kept quiet --}}
                <div class="flex h-48 flex-col justify-between text-right text-xs text-navy-600" aria-hidden="true">
                    <span>{{ $peso($revMax) }}</span><span>₱0</span>
                </div>
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-x-0 top-0 border-t border-dashed border-rule" aria-hidden="true"></div>
                    <ol class="flex h-48 items-end gap-0.5 border-b border-navy-300" aria-label="Revenue per day">
                        @foreach ($revenueDays as $day)
                            <li class="group relative flex h-full flex-1 items-end justify-center">
                                <div @class(['w-full max-w-7 rounded-t bg-navy-700 transition-colors group-hover:bg-navy-900', 'min-h-0.5' => $day['total'] > 0])
                                     @style(['height: '.($day['total'] / $revMax * 100).'%'])></div>
                                {{-- tooltip --}}
                                <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 rounded-md bg-navy-900 px-2.5 py-1.5 text-xs whitespace-nowrap text-line shadow-float group-hover:block" role="tooltip">
                                    {{ $day['date']->format('D, M j') }}<br><span class="font-condensed text-sm font-bold">{{ $peso($day['total']) }}</span>
                                </div>
                                <span class="sr-only">{{ $day['date']->format('M j') }}: {{ $peso($day['total']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <div class="mt-1.5 flex justify-between text-xs text-navy-600" aria-hidden="true">
                        <span>{{ $revenueDays->first()['date']->format('M j') }}</span>
                        <span>{{ $revenueDays->get(7)['date']->format('M j') }}</span>
                        <span>Today</span>
                    </div>
                </div>
            </div>
            @if ($revTotal == 0)
                <p class="mt-3 text-sm text-navy-600">No confirmed or completed bookings in the last 14 days yet. Revenue counts once a booking is confirmed.</p>
            @endif
        </section>

        {{-- ── Most-rented paddles this month ─────────────────────── --}}
        <section class="rounded-xl bg-white p-5 ring-1 ring-rule" aria-labelledby="paddles-title">
            <h2 id="paddles-title" class="text-lg font-bold">Most-rented paddles this month</h2>
            @if ($topPaddles->isEmpty())
                <p class="mt-4 text-sm text-navy-600">No paddle rentals this month yet.</p>
            @else
                <ol class="mt-5 space-y-4">
                    @foreach ($topPaddles as $p)
                        <li>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate font-semibold">{{ $p->brand ? $p->brand.' ' : '' }}{{ $p->name }}</span>
                                <span class="font-condensed text-base font-bold whitespace-nowrap">{{ $p->units }} {{ Str::plural('rental', (int) $p->units) }}</span>
                            </div>
                            <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-navy-100">
                                <div class="h-full rounded-full bg-navy-700" @style(['width: '.((int) $p->units / $paddleMax * 100).'%'])></div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</div>
