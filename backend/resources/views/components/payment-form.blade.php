{{-- Inline "record payment" form. Used inside components that use the RecordsPayments trait. --}}
@props(['booking', 'selected' => 'cash'])

<form wire:submit="recordPayment" {{ $attributes->class(['rounded-lg bg-concrete/70 p-3 ring-1 ring-rule']) }}>
    <p class="text-sm font-semibold">Record payment of ₱{{ number_format($booking->balanceDue(), 2) }}</p>
    <div class="mt-2 flex flex-wrap gap-1.5" role="radiogroup" aria-label="Payment method">
        @foreach (\App\Enums\PaymentMethod::cases() as $method)
            <label class="cursor-pointer">
                <input type="radio" wire:model.live="paymentMethod" value="{{ $method->value }}" class="peer sr-only">
                <span class="inline-block rounded-md bg-white px-2.5 py-1 text-sm font-semibold ring-1 ring-rule peer-checked:bg-navy-900 peer-checked:text-line peer-checked:ring-navy-900 peer-focus-visible:ring-2 peer-focus-visible:ring-brand">{{ $method->label() }}</span>
            </label>
        @endforeach
    </div>
    @if (\App\Enums\PaymentMethod::tryFrom($selected)?->needsReference())
        <label class="mt-2 block">
            <span class="sr-only">Reference number</span>
            <input type="text" wire:model="paymentReference" placeholder="Reference number" maxlength="100"
                   class="w-full rounded-md border-0 bg-white px-2.5 py-1.5 text-sm ring-1 ring-rule focus:ring-2 focus:ring-brand">
        </label>
    @endif
    @error('payment_reference') <p class="mt-1 text-sm font-medium text-red-700" role="alert">{{ $message }}</p> @enderror
    @error('payment') <p class="mt-1 text-sm font-medium text-red-700" role="alert">{{ $message }}</p> @enderror
    <div class="mt-3 flex gap-2">
        <button type="submit" class="btn-primary btn-sm flex-1">Mark as paid</button>
        <button type="button" wire:click="cancelPayment" class="btn-secondary btn-sm">Back</button>
    </div>
</form>
