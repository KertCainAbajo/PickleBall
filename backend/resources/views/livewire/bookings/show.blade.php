<div class="page">
<div class="mx-auto max-w-2xl">
    @php($fmt = fn ($t, $f = 'g:i A') => \Carbon\Carbon::parse($t)->format($f))
    @php($hoursLabel = rtrim(rtrim($booking->hours, '0'), '.'))

    <a href="{{ route('bookings.index') }}" wire:navigate class="font-medium text-navy-700 hover:text-navy-900 hover:underline">Back to my bookings</a>

    @if ($notice)
        <p class="mt-4 rounded-sm border-l-4 border-ball-dark bg-white px-4 py-3 font-medium" role="status">{{ $notice }}</p>
    @endif

    <article class="ticket mt-4" aria-labelledby="booking-title">
        {{-- Stub header: the court you're playing on --}}
        <div class="rounded-t-xl bg-navy-900 px-6 pt-5 pb-6 text-line">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 id="booking-title" class="font-display text-2xl">{{ $booking->court->name }}</h1>
                    <p class="mt-1 text-line/60">Booking ref <span class="font-condensed text-lg font-semibold tracking-wide text-line">{{ $booking->reference }}</span></p>
                </div>
                <x-status-badge :status="$booking->status" class="text-sm" />
            </div>
            <x-court-diagram
                :kitchen="$booking->status->isActive() ? 'rgb(242 102 9 / 0.85)' : null"
                class="mt-5 max-w-xs rounded-sm" />
        </div>

        <dl class="grid grid-cols-2 gap-x-6 gap-y-4 px-6 py-6 sm:grid-cols-3">
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
            @if ($booking->notes)
                <div class="col-span-2 sm:col-span-3">
                    <dt class="text-sm text-navy-600">Note for the front desk</dt>
                    <dd>{{ $booking->notes }}</dd>
                </div>
            @endif
        </dl>

        {{-- Paddles --}}
        <section class="border-t border-rule px-6 py-5" aria-labelledby="paddles-title">
            <div class="mb-2 flex items-center justify-between gap-3">
                <h2 id="paddles-title" class="font-semibold">Rental paddles</h2>
                @if ($booking->isModifiable())
                    <a href="{{ route('bookings.paddles', $booking) }}" wire:navigate class="btn-secondary btn-sm">Add paddles</a>
                @endif
            </div>

            @forelse ($booking->rentals as $rental)
                <div wire:key="rental-{{ $rental->id }}" class="flex items-center justify-between gap-4 border-b border-rule py-3 last:border-0">
                    <div>
                        <p class="font-medium">{{ $rental->quantity }} {{ Str::plural($rental->paddle->name, $rental->quantity) }}</p>
                        <p class="text-sm text-navy-600">₱{{ number_format($rental->unit_price, 0) }}/hr for {{ $hoursLabel }} {{ Str::plural('hour', (float) $booking->hours) }}, {{ $rental->quantity }} {{ Str::plural('paddle', $rental->quantity) }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="font-condensed text-lg font-semibold">₱{{ number_format($rental->subtotal, 2) }}</span>
                        @if ($booking->isModifiable())
                            <button type="button" wire:click="removePaddle({{ $rental->paddle_id }})"
                                    wire:confirm="Remove {{ $rental->paddle->name }} from this booking?"
                                    class="cursor-pointer text-sm font-semibold text-red-700 hover:underline">Remove</button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-navy-600">No paddles on this booking.</p>
            @endforelse
        </section>

        <div class="ticket-tear"></div>

        <dl class="space-y-1.5 px-6 pt-5 pb-6">
            <div class="flex justify-between"><dt class="text-navy-600">Court fee</dt><dd class="font-condensed text-lg font-semibold">₱{{ number_format($booking->court_fee, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-navy-600">Paddle fee</dt><dd class="font-condensed text-lg font-semibold">₱{{ number_format($booking->paddle_fee, 2) }}</dd></div>
            <div class="flex items-baseline justify-between pt-2">
                <dt class="text-lg font-semibold">Total</dt>
                <dd class="font-condensed text-4xl leading-none font-semibold">₱{{ number_format($booking->total_amount, 2) }}</dd>
            </div>
        </dl>
    </article>

    @error('booking')
        <p class="mt-4 rounded-sm border-l-4 border-red-700 bg-red-50 px-4 py-3 font-medium text-red-800" role="alert">{{ $message }}</p>
    @enderror

    @if ($booking->isModifiable())
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-navy-600">You can change or cancel this booking until {{ $fmt($booking->start_time) }} on {{ $booking->booking_date->format('M j') }}.</p>
            <button type="button" wire:click="cancel" wire:confirm="Cancel this booking? The court and any paddles will be released."
                    class="btn-secondary text-red-700 hover:border-red-700">Cancel booking</button>
        </div>
    @endif
</div>
</div>
