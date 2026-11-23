<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <p class="text-sm font-semibold text-court-700">Booking {{ $booking->reference }}</p>
        <h1 class="text-3xl font-bold tracking-tight">Need paddles?</h1>
        <p class="mt-1 text-zinc-600">
            Rent paddles for {{ $booking->court->name }} on {{ $booking->booking_date->format('D, M j') }},
            {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
            ({{ rtrim(rtrim($booking->hours, '0'), '.') }} hr{{ $booking->hours > 1 ? 's' : '' }}).
        </p>
    </div>

    @if ($booking->rentals->isNotEmpty())
        <div class="mb-4 rounded-xl border border-court-200 bg-court-50 px-4 py-3 text-sm text-court-800">
            Already on this booking:
            {{ $booking->rentals->map(fn ($r) => $r->quantity.' × '.$r->paddle->name)->join(', ') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($this->paddles as $paddle)
            @php $qty = $quantities[$paddle->id] ?? 0; @endphp
            <div wire:key="paddle-{{ $paddle->id }}" @class(['card flex flex-col gap-4 p-5 sm:flex-row sm:items-center', 'ring-2 ring-court-600' => $qty > 0])>
                <div class="flex-1">
                    <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">{{ $paddle->brand }}</p>
                    <h3 class="font-semibold">{{ $paddle->name }}</h3>
                    <p class="text-sm text-zinc-600">{{ $paddle->description }}</p>
                    <p class="mt-1 text-sm">
                        <span class="font-bold">₱{{ number_format($paddle->hourly_rate, 2) }}</span><span class="text-zinc-500">/hr each</span>
                        ·
                        @if ($paddle->available_quantity > 0)
                            <span class="text-court-700">{{ $paddle->available_quantity }} available</span>
                        @else
                            <span class="text-red-600">Fully rented for this time</span>
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" wire:click="decrement({{ $paddle->id }})" class="btn-secondary size-10 p-0 text-lg" @disabled($qty === 0) aria-label="Remove one {{ $paddle->name }}">−</button>
                    <span class="w-8 text-center text-lg font-bold" aria-live="polite">{{ $qty }}</span>
                    <button type="button" wire:click="increment({{ $paddle->id }})" class="btn-secondary size-10 p-0 text-lg" @disabled($qty >= $paddle->available_quantity) aria-label="Add one {{ $paddle->name }}">+</button>
                </div>
            </div>
        @empty
            <div class="card p-6 text-center text-zinc-600">No paddles are available for rent right now.</div>
        @endforelse
    </div>

    @error('paddles')
        <div class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $message }}</div>
    @enderror
    @error('booking')
        <div class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $message }}</div>
    @enderror

    {{-- Totals --}}
    <div class="card mt-6 p-5">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-zinc-500">Court fee</dt><dd>₱{{ number_format($booking->court_fee, 2) }}</dd></div>
            @if ((float) $booking->paddle_fee > 0)
                <div class="flex justify-between"><dt class="text-zinc-500">Paddles already rented</dt><dd>₱{{ number_format($booking->paddle_fee, 2) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-zinc-500">Paddles to add</dt><dd>₱{{ number_format($this->paddleTotal, 2) }}</dd></div>
            <div class="flex items-baseline justify-between border-t border-zinc-200 pt-3">
                <dt class="font-semibold">New total</dt>
                <dd class="text-2xl font-bold">₱{{ number_format((float) $booking->total_amount + $this->paddleTotal, 2) }}</dd>
            </div>
        </dl>

        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" wire:click="skip" class="btn-secondary">No thanks, skip</button>
            <button type="button" wire:click="save" class="btn-primary" @disabled($this->paddleTotal <= 0) wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Add paddles</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </div>
</div>
