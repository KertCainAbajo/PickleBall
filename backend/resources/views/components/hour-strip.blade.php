{{--
    Today's hours for one court as a strip: lime = free, hatched = booked, dim = already past.
    Props: hours (from BookingService::courtSlots), label (accessible court name).
--}}
@props(['hours' => [], 'label' => 'Court'])

@php
    $fmt = fn ($t) => \Carbon\Carbon::parse($t)->format('ga');
    $first = $hours[0]['start_time'] ?? null;
    $last = $hours ? end($hours)['end_time'] : null;
    $mid = $hours ? $hours[intdiv(count($hours), 2)]['start_time'] : null;
@endphp

<div {{ $attributes }}>
    <div class="flex h-3 gap-[2px] overflow-hidden rounded-sm bg-navy-900 p-[2px]" role="img"
         aria-label="{{ $label }} today: {{ collect($hours)->where('available', true)->count() }} free hours">
        @foreach ($hours as $hour)
            <span title="{{ $fmt($hour['start_time']) }}: {{ $hour['available'] ? 'free' : ($hour['taken'] ? 'booked' : 'past') }}"
                  @class([
                      'flex-1 rounded-[1px]',
                      'bg-ball' => $hour['available'],
                      'bg-brand/80' => $hour['taken'] && ! $hour['past'],
                      'bg-navy-700' => $hour['past'],
                  ])></span>
        @endforeach
    </div>
    @if ($first)
        <div class="mt-1 flex justify-between font-condensed text-xs font-medium text-navy-600">
            <span>{{ $fmt($first) }}</span><span>{{ $fmt($mid) }}</span><span>{{ $fmt($last) }}</span>
        </div>
    @endif
</div>
