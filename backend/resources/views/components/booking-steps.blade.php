{{--
    Where the player is in the booking flow: 1 Court & time, 2 Paddles, 3 Confirmed.
    Props: current (1-3).
--}}
@props(['current' => 1])

@php($steps = ['Court & time', 'Paddles', 'Confirmed'])

<ol class="flex items-center gap-2 sm:gap-3" aria-label="Booking progress">
    @foreach ($steps as $i => $label)
        @php($n = $i + 1)
        <li class="flex items-center gap-2 sm:gap-3" @if ($n === $current) aria-current="step" @endif>
            <span @class([
                'grid size-8 shrink-0 place-items-center rounded-full font-condensed text-base font-bold',
                'bg-ball text-navy-900' => $n < $current,
                'bg-brand text-navy-900 ring-4 ring-brand/25' => $n === $current,
                'border border-line/30 text-line/60' => $n > $current,
            ])>
                @if ($n < $current)
                    <svg viewBox="0 0 16 16" class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M3 8.5 6.5 12 13 4.5"/></svg>
                    <span class="sr-only">Done:</span>
                @else
                    {{ $n }}
                @endif
            </span>
            <span @class(['text-sm font-semibold whitespace-nowrap', 'text-line' => $n <= $current, 'hidden text-line/55 sm:inline' => $n > $current])>{{ $label }}</span>
            @if (! $loop->last)
                <span class="h-px w-5 bg-line/25 sm:w-8" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
