{{--
    Split-screen layout for Log in / Join the club:
    left = real photo with the club crest and what members can do; right = the form.
    Props: title, subtitle, image (path under public/), position (object-position for the photo).
    Slot: the form. Named slot "aside": short line under the form (e.g. link to the other page).
--}}
@props([
    'title',
    'subtitle' => null,
    'image' => 'images/cta-player.jpg',
    'position' => '50% 30%',
])

<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:py-14">
    <div class="grid overflow-hidden rounded-2xl bg-white shadow-lift ring-1 ring-rule lg:grid-cols-[1fr_1.05fr]">
        {{-- Photo side --}}
        <div class="relative isolate flex min-h-56 flex-col justify-end overflow-hidden bg-navy-900 p-7 text-line sm:min-h-72 sm:p-10 lg:min-h-[36rem]">
            <img src="{{ asset($image) }}" alt="" class="absolute inset-0 -z-10 size-full object-cover" @style(['object-position: '.$position]) aria-hidden="true">
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-navy-900 via-navy-900/45 to-transparent" aria-hidden="true"></div>

            <img src="{{ asset('images/logo-sm.png') }}" alt="" class="absolute top-6 left-7 h-16 w-auto drop-shadow-[0_8px_16px_rgb(0_0_0/0.5)] sm:left-10" width="105" height="112">

            <p class="font-display text-[clamp(1.75rem,3.2vw,2.5rem)] leading-[1.02]">Your court.<br>Your time.</p>
            <ul class="mt-5 hidden space-y-2.5 text-line/85 sm:block">
                @foreach (['See free hours live and book in a minute', 'Rent paddles for exactly your time slot', 'Change or cancel any time before you play'] as $point)
                    <li class="flex items-start gap-2.5">
                        <span class="mt-1.5 size-2.5 shrink-0 rounded-full bg-ball" aria-hidden="true"></span>{{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Form side --}}
        <div class="flex flex-col justify-center px-6 py-10 sm:px-12 lg:px-14">
            <h1 class="page-title text-[clamp(2rem,3.5vw,2.75rem)]">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-2 text-lg text-navy-700">{{ $subtitle }}</p>
            @endif

            <div class="mt-8">
                {{ $slot }}
            </div>

            @isset($aside)
                <p class="mt-8 border-t border-rule pt-6 text-center text-navy-700">{{ $aside }}</p>
            @endisset
        </div>
    </div>
</div>
