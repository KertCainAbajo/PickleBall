<div class="page">
    @php($fmt = fn ($t, $f = 'g:i A') => \Carbon\Carbon::parse($t)->format($f))
    @php($hours = (float) $booking->hours)

    <header class="mb-8 max-w-2xl">
        <h1 class="page-title text-4xl">Rent paddles</h1>
        <p class="mt-3 text-navy-700">
            Your court is booked. Add paddles for the same time, {{ $fmt($booking->start_time) }} to {{ $fmt($booking->end_time) }} on {{ $booking->booking_date->format('l, F j') }}, or skip this step.
        </p>
    </header>

    <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-labelledby="paddle-list" class="min-w-0">
            <h2 id="paddle-list" class="sr-only">Paddles</h2>

            @if ($booking->rentals->isNotEmpty())
                <p class="mb-4 rounded-sm border-l-4 border-navy-700 bg-white px-4 py-3 text-navy-800">
                    Already on this booking: {{ $booking->rentals->map(fn ($r) => $r->quantity.' '.Str::plural($r->paddle->name, $r->quantity))->join(', ', ' and ') }}.
                </p>
            @endif

            <ul class="divide-y divide-rule border-y border-rule">
                @forelse ($this->paddles as $paddle)
                    @php($qty = $quantities[$paddle->id] ?? 0)
                    @php($soldOut = $paddle->available_quantity === 0)
                    <li wire:key="paddle-{{ $paddle->id }}" class="flex flex-wrap items-center gap-x-6 gap-y-3 py-5">
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-semibold">{{ $paddle->name }}
                                @if ($paddle->brand)<span class="font-normal text-navy-600">by {{ $paddle->brand }}</span>@endif
                            </p>
                            @if ($paddle->description)
                                <p class="text-navy-600">{{ $paddle->description }}</p>
                            @endif
                            <p class="mt-1 text-sm">
                                <span class="font-condensed text-base font-semibold">₱{{ number_format($paddle->hourly_rate, 0) }}/hr each</span>
                                @if ($soldOut)
                                    <span class="ml-2 text-navy-600">All rented out for your time</span>
                                @else
                                    <span class="ml-2 inline-flex items-center gap-1.5 font-medium text-navy-700"><span class="size-2 rounded-full bg-ball-dark" aria-hidden="true"></span>{{ $paddle->available_quantity }} free for your time</span>
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-1" role="group" aria-label="Quantity of {{ $paddle->name }}">
                            <button type="button" wire:click="decrement({{ $paddle->id }})" class="btn-secondary size-11 p-0 text-xl" @disabled($qty === 0) aria-label="One less {{ $paddle->name }}">&minus;</button>
                            <output class="w-12 text-center font-condensed text-2xl font-semibold" aria-live="polite">{{ $qty }}</output>
                            <button type="button" wire:click="increment({{ $paddle->id }})" class="btn-secondary size-11 p-0 text-xl" @disabled($qty >= $paddle->available_quantity) aria-label="One more {{ $paddle->name }}">+</button>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-navy-700">No paddles are available to rent right now. You can still play with your own.</li>
                @endforelse
            </ul>
        </section>

        {{-- Ticket with running total --}}
        <aside class="lg:sticky lg:top-6" aria-labelledby="ticket-title">
            <div class="ticket">
                <div class="rounded-t-xl bg-navy-900 px-5 pt-4 pb-5 text-line">
                    <h2 id="ticket-title" class="font-display text-xl">Your booking</h2>
                    <p class="mt-1 text-sm text-line/60">{{ $booking->court->name }}, ref {{ $booking->reference }}</p>
                </div>

                <dl class="space-y-2 px-5 py-5">
                    <div class="flex justify-between gap-4">
                        <dt class="text-navy-600">Court, {{ rtrim(rtrim($booking->hours, '0'), '.') }} {{ Str::plural('hour', $hours) }}</dt>
                        <dd class="font-condensed text-lg font-semibold">₱{{ number_format($booking->court_fee, 2) }}</dd>
                    </div>
                    @if ((float) $booking->paddle_fee > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-navy-600">Paddles already added</dt>
                            <dd class="font-condensed text-lg font-semibold">₱{{ number_format($booking->paddle_fee, 2) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4">
                        <dt class="text-navy-600">Paddles you're adding</dt>
                        <dd class="font-condensed text-lg font-semibold">₱{{ number_format($this->paddleTotal, 2) }}</dd>
                    </div>
                </dl>

                <div class="ticket-tear"></div>

                <div class="px-5 pt-4 pb-5">
                    <div class="flex items-baseline justify-between">
                        <span class="font-semibold">Total</span>
                        <span class="font-condensed text-4xl leading-none font-semibold">₱{{ number_format((float) $booking->total_amount + $this->paddleTotal, 2) }}</span>
                    </div>

                    @error('paddles')
                        <p class="mt-4 rounded-sm border-l-4 border-red-700 bg-red-50 px-3 py-2 text-sm font-medium text-red-800" role="alert">{{ $message }}</p>
                    @enderror
                    @error('booking')
                        <p class="mt-4 rounded-sm border-l-4 border-red-700 bg-red-50 px-3 py-2 text-sm font-medium text-red-800" role="alert">{{ $message }}</p>
                    @enderror

                    <button type="button" wire:click="save" class="btn-accent mt-4 w-full py-3 text-base"
                            @disabled($this->paddleTotal <= 0) wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Add paddles</span>
                        <span wire:loading wire:target="save">Adding paddles</span>
                    </button>
                    <button type="button" wire:click="skip" class="btn mt-2 w-full text-navy-700 hover:text-navy-900">Skip, I have my own</button>
                </div>
            </div>
        </aside>
    </div>
</div>
