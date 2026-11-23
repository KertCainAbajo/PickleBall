{{--
    Dark court-surface band at the top of inner pages.
    Props: title, subtitle. Slot: actions on the right.
--}}
@props(['title', 'subtitle' => null])

<header class="court-surface relative overflow-hidden text-line">
    {{-- a corner of court lines, as if the band were part of the floor --}}
    <svg class="pointer-events-none absolute -right-10 -bottom-16 hidden w-[28rem] opacity-[0.12] md:block" viewBox="0 0 440 200" fill="none" stroke="currentColor" stroke-width="4" aria-hidden="true">
        <rect x="2" y="2" width="436" height="196" /><line x1="150" y1="2" x2="150" y2="198" /><line x1="290" y1="2" x2="290" y2="198" />
        <line x1="2" y1="100" x2="150" y2="100" /><line x1="290" y1="100" x2="438" y2="100" />
    </svg>
    <div class="relative mx-auto flex max-w-6xl flex-wrap items-end justify-between gap-6 px-4 pt-10 pb-9 sm:px-6">
        <div class="max-w-2xl">
            <h1 class="font-display text-[clamp(2rem,4.5vw,2.75rem)] leading-none">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-3 text-lg text-line/70">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $slot }}
    </div>
</header>
