<div class="mx-auto max-w-3xl">
    <a href="{{ route('bookings.index') }}" wire:navigate class="text-sm font-medium text-court-700 hover:underline">← My bookings</a>

    {{-- Messages from actions on this page (remove paddle / cancel) --}}
    @if ($notice)
        <div class="mt-4 rounded-xl border border-court-200 bg-court-50 px-4 py-3 text-sm font-medium text-court-800" role="status">{{ $notice }}</div>
    @endif

    <div class="card mt-4 overflow-hidden">
        <div class="flex flex-wrap items-start justify-between gap-4 bg-court-800 px-6 py-5 text-white">
            <div>
                <p class="text-sm text-white/70">Reference</p>
                <p class="text-xl font-bold tracking-wide">{{ $booking->reference }}</p>
            </div>
            <x-status-badge :status="$booking->status" class="text-sm" />
        </div>

        <div class="grid gap-6 p-6 sm:grid-cols-3">
            <div>
                <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Court</p>
                <p class="mt-1 font-semibold">{{ $booking->court->name }}</p>
                <p class="text-sm text-zinc-500">{{ $booking->court->surface_type }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Date</p>
                <p class="mt-1 font-semibold">{{ $booking->booking_date->format('l, F j, Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Time</p>
                <p class="mt-1 font-semibold">
                    {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
                </p>
                <p class="text-sm text-zinc-500">{{ rtrim(rtrim($booking->hours, '0'), '.') }} hour{{ $booking->hours > 1 ? 's' : '' }}</p>
            </div>
        </div>

        @if ($booking->notes)
            <div class="border-t border-zinc-100 px-6 py-4 text-sm">
                <span class="font-semibold">Notes:</span> {{ $booking->notes }}
            </div>
        @endif

        {{-- Paddle rentals --}}
        <div class="border-t border-zinc-100 px-6 py-5">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="font-semibold">Paddle rentals</h2>
                @if ($booking->isModifiable())
                    <a href="{{ route('bookings.paddles', $booking) }}" wire:navigate class="btn-secondary btn-sm">+ Rent paddles</a>
                @endif
            </div>

            @forelse ($booking->rentals as $rental)
                <div wire:key="rental-{{ $rental->id }}" class="flex items-center justify-between gap-4 border-b border-zinc-100 py-3 last:border-0">
                    <div>
                        <p class="font-medium">{{ $rental->quantity }} × {{ $rental->paddle->name }}</p>
                        <p class="text-xs text-zinc-500">₱{{ number_format($rental->unit_price, 2) }}/hr × {{ rtrim(rtrim($booking->hours, '0'), '.') }} hr × {{ $rental->quantity }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold">₱{{ number_format($rental->subtotal, 2) }}</span>
                        @if ($booking->isModifiable())
                            <button type="button" wire:click="removePaddle({{ $rental->paddle_id }})"
                                    wire:confirm="Remove {{ $rental->paddle->name }} from this booking?"
                                    class="text-xs font-semibold text-red-600 hover:underline cursor-pointer">Remove</button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-zinc-500">No paddles rented for this booking.</p>
            @endforelse
        </div>

        {{-- Totals --}}
        <dl class="space-y-2 border-t border-zinc-100 bg-zinc-50 px-6 py-5 text-sm">
            <div class="flex justify-between"><dt class="text-zinc-500">Court fee</dt><dd>₱{{ number_format($booking->court_fee, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-zinc-500">Paddle fee</dt><dd>₱{{ number_format($booking->paddle_fee, 2) }}</dd></div>
            <div class="flex items-baseline justify-between border-t border-zinc-200 pt-3">
                <dt class="font-semibold">Total</dt>
                <dd class="text-2xl font-bold">₱{{ number_format($booking->total_amount, 2) }}</dd>
            </div>
        </dl>

        @error('booking')
            <div class="mx-6 mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $message }}</div>
        @enderror

        @if ($booking->isModifiable())
            <div class="flex justify-end border-t border-zinc-100 px-6 py-4">
                <button type="button" wire:click="cancel" wire:confirm="Cancel this booking? This frees the court and any rented paddles."
                        class="btn-danger btn-sm">Cancel booking</button>
            </div>
        @endif
    </div>
</div>
