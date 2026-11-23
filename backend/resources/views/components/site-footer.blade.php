{{--
    Site footer: crest + call to action, live club hours, links, and a painted
    court baseline as the divider. Club hours are cached (cleared when a court changes).
--}}
@php
    $clubHours = cache()->remember(\App\Models\Court::HOURS_CACHE_KEY, 600, fn () => [
        \App\Models\Court::active()->min('opening_time'),
        \App\Models\Court::active()->max('closing_time'),
    ]);

    [$opens, $closes] = $clubHours;
    $hasHours = $opens && $closes;
    $fmt = fn ($t) => \Carbon\Carbon::parse($t)->format('g:i A');

    if ($hasHours) {
        $now = now()->format('H:i:s');
        $isOpen = $now >= $opens && $now < $closes;
        // Bookings are by the hour, so the last one starts an hour before closing.
        $lastStart = \Carbon\Carbon::parse($closes)->subHour();
    }

    $link = 'text-line/70 transition-colors hover:text-line';
@endphp

<footer class="relative isolate overflow-hidden bg-navy-900 text-line/70">
    {{-- aerial photo of real courts, deep under navy --}}
    <img src="{{ asset('images/courts-aerial-sm.jpg') }}" alt="" width="1400" height="788" loading="lazy"
         class="pointer-events-none absolute inset-0 -z-10 size-full object-cover opacity-45" aria-hidden="true">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-navy-900 via-navy-900/80 to-navy-900" aria-hidden="true"></div>

    <div class="mx-auto max-w-6xl px-4 pt-16 sm:px-6">
        <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_0.8fr_0.8fr]">
            {{-- Club --}}
            <div>
                <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-4 rounded-sm">
                    <img src="{{ asset('images/logo-sm.png') }}" alt="" class="h-20 w-auto drop-shadow-[0_8px_16px_rgb(0_0_0/0.5)]" width="105" height="112">
                    <span class="wordmark text-3xl">PickleBall</span>
                    <span class="sr-only">Club home</span>
                </a>
                <p class="mt-5 max-w-xs text-lg leading-relaxed">Courts and rental paddles, booked online in under a minute.</p>
                <a href="{{ route('book') }}" wire:navigate class="btn-accent mt-6 px-6 py-3 text-base">Book a court</a>
            </div>

            {{-- Hours, with live open/closed status --}}
            <div>
                <h2 class="font-display text-lg text-line">Club hours</h2>
                @if ($hasHours)
                    <p class="mt-4 inline-flex items-center gap-2 rounded-full border border-line/15 bg-navy-800/70 py-1 pr-3 pl-2.5 text-sm font-semibold">
                        <span @class(['size-2 rounded-full', 'bg-ball' => $isOpen, 'bg-line/40' => ! $isOpen]) aria-hidden="true"></span>
                        @if ($isOpen)
                            <span class="text-ball">Open now</span>
                        @else
                            <span class="text-line/80">Closed now, opens {{ $fmt($opens) }}</span>
                        @endif
                    </p>
                    <dl class="mt-4 space-y-3">
                        <div>
                            <dt class="text-sm">Every day</dt>
                            <dd class="font-condensed text-2xl font-semibold text-line">{{ $fmt($opens) }} to {{ $fmt($closes) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm">Last booking starts</dt>
                            <dd class="font-condensed text-lg font-semibold text-line">{{ $lastStart->format('g:i A') }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-4">Hours will be posted soon.</p>
                @endif
            </div>

            {{-- Play --}}
            <nav aria-labelledby="footer-play">
                <h2 id="footer-play" class="font-display text-lg text-line">Play</h2>
                <ul class="mt-4 space-y-3">
                    <li><a href="{{ route('book') }}" wire:navigate class="{{ $link }}">Book a court</a></li>
                    <li><a href="{{ route('home') }}#courts" class="{{ $link }}">Courts today</a></li>
                    <li><a href="{{ route('home') }}#paddles" class="{{ $link }}">Rental paddles</a></li>
                </ul>
            </nav>

            {{-- Account --}}
            <nav aria-labelledby="footer-account">
                <h2 id="footer-account" class="font-display text-lg text-line">Account</h2>
                <ul class="mt-4 space-y-3">
                    @auth
                        <li><a href="{{ route('bookings.index') }}" wire:navigate class="{{ $link }}">My bookings</a></li>
                        @if (auth()->user()->isAdmin())
                            <li><a href="{{ route('admin.dashboard') }}" wire:navigate class="{{ $link }}">Admin</a></li>
                        @endif
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="{{ $link }} cursor-pointer">Log out</button>
                            </form>
                        </li>
                    @else
                        <li><a href="{{ route('login') }}" wire:navigate class="{{ $link }}">Log in</a></li>
                        <li><a href="{{ route('register') }}" wire:navigate class="{{ $link }}">Create an account</a></li>
                    @endauth
                </ul>
            </nav>
        </div>

        {{-- Painted court baseline: white line, orange kitchen in the middle, net posts --}}
        <div class="relative mt-14 h-[3px] bg-line/70" aria-hidden="true">
            <div class="absolute inset-y-0 left-1/2 w-[18%] -translate-x-1/2 bg-brand"></div>
            <div class="absolute top-1/2 left-1/2 h-4 w-[3px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-line"></div>
            <div class="absolute top-1/2 left-0 size-2.5 -translate-y-1/2 rounded-full bg-line"></div>
            <div class="absolute top-1/2 right-0 size-2.5 -translate-y-1/2 rounded-full bg-line"></div>
        </div>
        <div class="h-12" aria-hidden="true"></div>
    </div>
</footer>
