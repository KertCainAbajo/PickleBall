<div>
    @php
        $fmt = fn ($t, $f = 'g:i A') => \Carbon\Carbon::parse($t)->format($f);
        $hoursLabel = rtrim(rtrim($booking->hours, '0'), '.');
        $status = $booking->status;
        $isCancelled = $status === \App\Enums\BookingStatus::Cancelled;
        $startsAt = $booking->startsAt();
        $upcoming = $status->isActive() && $startsAt->isFuture();

        // Progress: 1 booked, 2 confirmed by the club, 3 played.
        $reached = match ($status) {
            \App\Enums\BookingStatus::Pending => 1,
            \App\Enums\BookingStatus::Confirmed => 2,
            \App\Enums\BookingStatus::Completed => 3,
            default => 0,
        };
        $progress = [
            ['Booked', $booking->created_at?->format('M j, g:i A')],
            ['Confirmed by the club', $reached >= 2 ? 'Your court is held for you' : 'Waiting for the club to confirm'],
            ['Played', $reached >= 3 ? 'Thanks for playing' : $startsAt->format('D, M j, g:i A')],
        ];
    @endphp

    <x-page-header :title="$booking->court->name" :image="$booking->court->image_path"
                   :subtitle="$booking->booking_date->format('l, F j').', '.$fmt($booking->start_time).' to '.$fmt($booking->end_time)">
        <div class="flex flex-col items-start gap-2 sm:items-end">
            <x-status-badge :status="$status" class="px-3 py-1 text-sm" />
            @if ($upcoming)
                <p class="text-line/80">Starts in <span class="font-condensed text-lg font-bold text-ball">{{ $startsAt->diffForHumans(now(), \Carbon\CarbonInterface::DIFF_ABSOLUTE, false, 2) }}</span></p>
            @endif
        </div>
    </x-page-header>

    <div class="page">
        <a href="{{ route('bookings.index') }}" wire:navigate class="inline-flex items-center gap-1.5 font-semibold text-navy-700 hover:text-navy-900">
            <svg viewBox="0 0 16 16" class="size-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 3 5 8l5 5"/></svg>
            My bookings
        </a>

        @if ($notice)
            <p class="mt-5 flex items-center gap-3 rounded-xl border-l-4 border-ball-dark bg-white px-5 py-4 font-semibold shadow-lift" role="status">
                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-ball text-navy-900" aria-hidden="true">
                    <svg viewBox="0 0 16 16" class="size-4" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 8.5 6.5 12 13 4.5"/></svg>
                </span>
                {{ $notice }}
            </p>
        @endif

        <div class="mt-6 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-6">

                {{-- ── Status ─────────────────────────────────────────── --}}
                <section class="rounded-xl bg-white p-6 ring-1 ring-rule" aria-labelledby="status-title">
                    <h2 id="status-title" class="text-lg font-bold">Booking status</h2>
                    @if ($isCancelled)
                        <p class="mt-3 rounded-lg bg-navy-50 px-4 py-3 text-navy-800">
                            This booking was cancelled{{ $booking->cancelled_at ? ' on '.$booking->cancelled_at->format('M j, g:i A') : '' }}. The court and any paddles were released.
                        </p>
                    @else
                        <ol class="mt-5 grid gap-5 sm:grid-cols-3">
                            @foreach ($progress as $i => [$label, $detail])
                                @php
                                    $n = $i + 1;
                                    $done = $n <= $reached;
                                    $current = $n === $reached + 1;
                                @endphp
                                <li class="relative flex gap-3 sm:flex-col">
                                    <div class="flex items-center gap-3">
                                        <span @class([
                                            'grid size-9 shrink-0 place-items-center rounded-full font-condensed text-lg font-bold',
                                            'bg-ball text-navy-900' => $done,
                                            'bg-brand text-navy-900 ring-4 ring-brand/20' => $current,
                                            'bg-navy-100 text-navy-600' => ! $done && ! $current,
                                        ])>
                                            @if ($done)
                                                <svg viewBox="0 0 16 16" class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M3 8.5 6.5 12 13 4.5"/></svg>
                                            @else
                                                {{ $n }}
                                            @endif
                                        </span>
                                        @unless ($loop->last)
                                            <span @class(['hidden h-0.5 flex-1 rounded-full sm:block', 'bg-ball-dark' => $done, 'bg-navy-100' => ! $done]) aria-hidden="true"></span>
                                        @endunless
                                    </div>
                                    <div>
                                        <p @class(['font-semibold', 'text-navy-600' => ! $done && ! $current])>{{ $label }}</p>
                                        <p class="text-sm text-navy-600">{{ $detail }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                {{-- ── Details ─────────────────────────────────────────── --}}
                <section class="rounded-xl bg-white p-6 ring-1 ring-rule" aria-labelledby="details-title">
                    <h2 id="details-title" class="text-lg font-bold">Details</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-[1fr_1.4fr_0.7fr_1fr]">
                        <div>
                            <dt class="text-sm text-navy-600">Day</dt>
                            <dd class="font-semibold">{{ $booking->booking_date->format('D, M j, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-navy-600">Time</dt>
                            <dd class="font-semibold">{{ $fmt($booking->start_time) }} to {{ $fmt($booking->end_time) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-navy-600">Length</dt>
                            <dd class="font-semibold">{{ $hoursLabel }} {{ Str::plural('hour', (float) $booking->hours) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-navy-600">Court</dt>
                            <dd class="font-semibold">{{ $booking->court->name }}@if ($booking->court->surface_type), {{ strtolower($booking->court->surface_type) }}@endif</dd>
                        </div>
                        @if ($booking->notes)
                            <div class="col-span-2 sm:col-span-4">
                                <dt class="text-sm text-navy-600">Note for the front desk</dt>
                                <dd>{{ $booking->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                {{-- ── Paddles ─────────────────────────────────────────── --}}
                <section class="rounded-xl bg-white p-6 ring-1 ring-rule" aria-labelledby="paddles-title">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="paddles-title" class="text-lg font-bold">Rental paddles</h2>
                        @if ($booking->isModifiable())
                            <a href="{{ route('bookings.paddles', $booking) }}" wire:navigate class="btn-secondary btn-sm">Add paddles</a>
                        @endif
                    </div>

                    <ul class="mt-3 divide-y divide-rule">
                        @forelse ($booking->rentals as $rental)
                            <li wire:key="rental-{{ $rental->id }}" class="flex items-center gap-4 py-4">
                                <div class="grid aspect-[4/3] w-20 shrink-0 place-items-center overflow-hidden rounded-lg bg-concrete ring-1 ring-rule">
                                    @if ($rental->paddle->image_url)
                                        <img src="{{ $rental->paddle->image_url }}" alt="" class="size-full object-cover" loading="lazy">
                                    @else
                                        <x-paddle-art class="h-12 w-auto -rotate-12" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold">{{ $rental->quantity }} × {{ $rental->paddle->brand ? $rental->paddle->brand.' ' : '' }}{{ $rental->paddle->name }}</p>
                                    <p class="text-sm text-navy-600">₱{{ number_format($rental->unit_price, 0) }}/hr for {{ $hoursLabel }} {{ Str::plural('hour', (float) $booking->hours) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-condensed text-xl font-bold">₱{{ number_format($rental->subtotal, 2) }}</p>
                                    @if ($booking->isModifiable())
                                        <button type="button" wire:click="removePaddle({{ $rental->paddle_id }})"
                                                wire:confirm="Remove {{ $rental->paddle->name }} from this booking?"
                                                class="cursor-pointer text-sm font-semibold text-red-700 hover:underline">Remove</button>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="py-4 text-navy-600">
                                No paddles on this booking.
                                @if ($booking->isModifiable())
                                    <a href="{{ route('bookings.paddles', $booking) }}" wire:navigate class="font-semibold text-navy-900 underline decoration-brand decoration-2 underline-offset-4">Rent some</a> if you need them.
                                @endif
                            </li>
                        @endforelse
                    </ul>
                </section>
            </div>

            {{-- ── Ticket: reference, total, actions ─────────────────────── --}}
            <aside class="lg:sticky lg:top-24" aria-labelledby="ticket-title">
                <div class="ticket overflow-hidden">
                    <div class="bg-navy-900 px-5 pt-5 pb-6 text-line">
                        <h2 id="ticket-title" class="text-sm font-semibold text-line/70">Booking reference</h2>
                        <p class="mt-1 font-condensed text-3xl font-bold tracking-wider text-ball">{{ $booking->reference }}</p>
                        <p class="mt-1 text-sm text-line/70">Keep this code handy when you come to play.</p>
                    </div>

                    <dl class="space-y-2 px-5 py-5">
                        <div class="flex justify-between"><dt class="text-navy-600">Court fee</dt><dd class="font-condensed text-lg font-bold">₱{{ number_format($booking->court_fee, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-navy-600">Paddle fee</dt><dd class="font-condensed text-lg font-bold">₱{{ number_format($booking->paddle_fee, 2) }}</dd></div>
                    </dl>

                    <div class="ticket-tear"></div>

                    <div class="px-5 pt-4 pb-5">
                        <div class="flex items-baseline justify-between">
                            <span class="text-lg font-semibold">Total</span>
                            <span class="font-condensed text-4xl leading-none font-bold">₱{{ number_format($booking->total_amount, 2) }}</span>
                        </div>

                        @error('booking')
                            <p class="mt-4 rounded-md border-l-4 border-red-700 bg-red-50 px-3 py-2 text-sm font-medium text-red-800" role="alert">{{ $message }}</p>
                        @enderror

                        @if ($booking->isModifiable())
                            <a href="{{ route('bookings.paddles', $booking) }}" wire:navigate class="btn-accent mt-5 w-full py-3 text-base">Add paddles</a>
                            <button type="button" wire:click="cancel" wire:confirm="Cancel this booking? The court and any paddles will be released."
                                    class="btn mt-2 w-full text-red-700 hover:bg-red-50">Cancel booking</button>
                            <p class="mt-3 text-center text-sm text-navy-600">Changes allowed until {{ $fmt($booking->start_time) }} on {{ $booking->booking_date->format('M j') }}.</p>
                        @else
                            <a href="{{ route('book') }}" wire:navigate class="btn-primary mt-5 w-full py-3 text-base">Book another court</a>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
