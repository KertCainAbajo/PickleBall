@props(['status'])

@php
    $status = $status instanceof \App\Enums\BookingStatus ? $status : \App\Enums\BookingStatus::from($status);

    $classes = match ($status) {
        \App\Enums\BookingStatus::Pending => 'bg-amber-100 text-amber-800',
        \App\Enums\BookingStatus::Confirmed => 'bg-court-100 text-court-800',
        \App\Enums\BookingStatus::Completed => 'bg-sky-100 text-sky-800',
        \App\Enums\BookingStatus::Cancelled => 'bg-zinc-200 text-zinc-600',
    };
@endphp

<span {{ $attributes->class(['badge', $classes]) }}>{{ ucfirst($status->value) }}</span>
