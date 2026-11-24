@props(['booking'])

@if ($booking->isPaid())
    <span {{ $attributes->class(['badge bg-emerald-100 text-emerald-800']) }}
          title="{{ $booking->payment_method?->label() }}{{ $booking->payment_reference ? ' · '.$booking->payment_reference : '' }}">Paid</span>
@elseif ((float) $booking->amount_paid > 0)
    <span {{ $attributes->class(['badge bg-amber-100 text-amber-800']) }}>₱{{ number_format($booking->balanceDue(), 0) }} due</span>
@else
    <span {{ $attributes->class(['badge bg-red-50 text-red-800']) }}>Unpaid</span>
@endif
