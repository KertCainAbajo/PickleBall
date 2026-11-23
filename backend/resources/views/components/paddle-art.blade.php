{{--
    A pickleball paddle, face up: rounded-rectangle face with edge guard, throat and wrapped grip.
    Props: face, edge, grip (colours).
--}}
@props([
    'face' => 'var(--color-brand)',
    'edge' => 'var(--color-navy-900)',
    'grip' => 'var(--color-navy-900)',
])

{{-- Size it with one dimension (e.g. w-24 or h-36); the other follows the viewBox ratio. --}}
<svg viewBox="0 0 100 210" aria-hidden="true" {{ $attributes->merge(['class' => 'block']) }}>
    {{-- face --}}
    <path d="M14 30 Q14 4 50 4 Q86 4 86 30 L86 104 Q86 128 62 132 L38 132 Q14 128 14 104 Z"
          fill="{{ $face }}" stroke="{{ $edge }}" stroke-width="6" stroke-linejoin="round" />
    {{-- face texture: a faint sweet-spot ring and brand stripe --}}
    <ellipse cx="50" cy="62" rx="22" ry="26" fill="none" stroke="rgb(255 255 255 / 0.22)" stroke-width="3" />
    <path d="M20 104 L80 104" stroke="rgb(255 255 255 / 0.25)" stroke-width="4" stroke-linecap="round" />
    {{-- throat --}}
    <path d="M38 132 L42 146 L58 146 L62 132 Z" fill="{{ $edge }}" />
    {{-- grip with wrap --}}
    <rect x="40" y="144" width="20" height="58" rx="5" fill="{{ $grip }}" />
    <g stroke="rgb(255 255 255 / 0.18)" stroke-width="2.5">
        <line x1="40" y1="156" x2="60" y2="150" />
        <line x1="40" y1="168" x2="60" y2="162" />
        <line x1="40" y1="180" x2="60" y2="174" />
        <line x1="40" y1="192" x2="60" y2="186" />
    </g>
    <rect x="38" y="198" width="24" height="8" rx="3" fill="{{ $edge }}" />
</svg>
