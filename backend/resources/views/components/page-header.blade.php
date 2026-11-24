{{--
    Dark band at the top of inner pages.
    Props: title, subtitle, image (optional photo under public/, shown under a navy fade).
    Slot: content on the right (actions, step tracker...).
--}}
@props(['title', 'subtitle' => null, 'image' => null])

<header class="relative isolate overflow-hidden bg-navy-900 text-line">
    @if ($image)
        <img src="{{ asset($image) }}" alt="" class="absolute inset-0 -z-10 size-full object-cover object-center" aria-hidden="true">
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-navy-900 via-navy-900/85 to-navy-900/40" aria-hidden="true"></div>
    @else
        {{-- a corner of court lines, as if the band were part of the floor --}}
        <svg class="pointer-events-none absolute -right-10 -bottom-16 -z-10 hidden w-[28rem] opacity-[0.12] md:block" viewBox="0 0 440 200" fill="none" stroke="currentColor" stroke-width="4" aria-hidden="true">
            <rect x="2" y="2" width="436" height="196" /><line x1="150" y1="2" x2="150" y2="198" /><line x1="290" y1="2" x2="290" y2="198" />
            <line x1="2" y1="100" x2="150" y2="100" /><line x1="290" y1="100" x2="438" y2="100" />
        </svg>
    @endif
    <div class="mx-auto flex max-w-6xl flex-wrap items-end justify-between gap-6 px-4 pt-10 pb-9 sm:px-6">
        <div class="max-w-2xl">
            <h1 class="font-display text-[clamp(2rem,4.5vw,2.75rem)] leading-none">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-3 text-lg text-line/75">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $slot }}
    </div>
</header>
