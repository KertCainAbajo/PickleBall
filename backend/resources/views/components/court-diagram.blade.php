{{--
    Overhead pickleball court, drawn to scale: 44 × 20 ft, kitchen (non-volley zone)
    7 ft each side of the net, centre lines from kitchen line to baseline.
    1 unit = 0.1 ft, so the viewBox is 440 × 200 plus a run-off margin.

    Props:
      surface  court colour
      lines    painted line colour
      kitchen  kitchen zone colour (null = same as surface)
      net      net colour
--}}
@props([
    'surface' => 'var(--color-navy-700)',
    'lines' => 'var(--color-line)',
    'kitchen' => 'rgb(242 102 9 / 0.85)',
    'net' => 'var(--color-line)',
])

<svg viewBox="-16 -16 472 232" role="img" aria-hidden="true" {{ $attributes->merge(['class' => 'block w-full h-auto']) }}>
    {{-- run-off area --}}
    <rect x="-16" y="-16" width="472" height="232" fill="{{ $surface }}" opacity="0.55" />
    {{-- playing surface --}}
    <rect x="0" y="0" width="440" height="200" fill="{{ $surface }}" />
    {{-- kitchens --}}
    @if ($kitchen)
        <rect x="150" y="0" width="70" height="200" fill="{{ $kitchen }}" />
        <rect x="220" y="0" width="70" height="200" fill="{{ $kitchen }}" />
    @endif
    {{-- lines --}}
    <g fill="none" stroke="{{ $lines }}" stroke-width="4">
        <rect x="0" y="0" width="440" height="200" />
        <line x1="150" y1="0" x2="150" y2="200" />
        <line x1="290" y1="0" x2="290" y2="200" />
        <line x1="0" y1="100" x2="150" y2="100" />
        <line x1="290" y1="100" x2="440" y2="100" />
    </g>
    {{-- net, slightly past the sidelines --}}
    <line x1="220" y1="-10" x2="220" y2="210" stroke="{{ $net }}" stroke-width="6" stroke-dasharray="2 4" />
    <circle cx="220" cy="-10" r="4" fill="{{ $net }}" />
    <circle cx="220" cy="210" r="4" fill="{{ $net }}" />
</svg>
