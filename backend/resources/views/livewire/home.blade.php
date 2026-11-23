<div>
    @php($time = fn ($t) => \Carbon\Carbon::parse($t)->format('g:i A'))
    @php($short = fn ($t) => \Carbon\Carbon::parse($t)->format('g A'))

    {{-- ============================================================
         HERO: a real night game on court, with the copy over a navy
         fade on the left. Replace public/images/hero-court*.jpg with
         a photo of your own club to personalise it.
         ============================================================ --}}
    <section class="relative bg-navy-900 text-line">
        {{-- photo + overlays (clipped here so the scoreboard can overhang the section) --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <img src="{{ asset('images/hero-court.jpg') }}"
                 srcset="{{ asset('images/hero-court-sm.jpg') }} 1000w, {{ asset('images/hero-court.jpg') }} 2400w"
                 sizes="100vw" alt="" width="2400" height="1600" fetchpriority="high" decoding="async"
                 class="size-full object-cover object-[86%_center] lg:object-[70%_center]">
            {{-- navy fade from the left for readable text; lighter on the right so the players show --}}
            <div class="absolute inset-0 bg-gradient-to-r from-navy-900 via-navy-900/85 to-navy-900/25"></div>
            {{-- bottom fade into the scoreboard --}}
            <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-navy-900 to-transparent"></div>
            {{-- top fade under the header --}}
            <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-navy-900/80 to-transparent"></div>
        </div>

        <div class="relative mx-auto max-w-6xl px-4 pt-20 pb-28 sm:px-6 sm:pt-24 sm:pb-36 lg:pt-32 lg:pb-44">
            <div class="max-w-2xl">
                @if ($stats['courts'] > 0)
                    <p class="inline-flex items-center gap-2.5 rounded-full border border-line/20 bg-navy-900/60 py-1.5 pr-4 pl-3 text-[0.9375rem] font-medium backdrop-blur-sm">
                        <span class="relative flex size-2.5" aria-hidden="true">
                            @if ($stats['open_now'] > 0)
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-ball opacity-60"></span>
                            @endif
                            <span @class(['relative inline-flex size-2.5 rounded-full', 'bg-ball' => $stats['open_now'] > 0, 'bg-line/40' => $stats['open_now'] === 0])></span>
                        </span>
                        @if ($stats['open_now'] > 0)
                            {{ $stats['open_now'] }} of {{ $stats['courts'] }} courts free right now
                        @elseif ($stats['free_hours'] > 0)
                            Courts open later today
                        @else
                            Book ahead for tomorrow
                        @endif
                    </p>
                @endif

                <h1 class="mt-6 font-display text-[clamp(2.5rem,5.6vw,4.5rem)] leading-[0.98] [text-shadow:0_2px_24px_rgb(16_28_44/0.6)]">
                    Book a court.<br>Grab a paddle.<br>Play.
                </h1>
                <p class="mt-6 max-w-lg text-lg leading-relaxed text-line/85">
                    Reserve an hour on one of our {{ $stats['courts'] }} courts in under a minute, and rent paddles for the same time if you didn't bring your own.
                </p>

                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('book') }}" wire:navigate class="btn-accent px-7 py-3.5 text-base shadow-[0_10px_30px_-10px_rgb(242_102_9/0.7)]">Book a court</a>
                    @guest
                        <a href="{{ route('register') }}" wire:navigate class="btn-ghost-light bg-navy-900/40 px-7 py-3.5 text-base backdrop-blur-sm">Create an account</a>
                    @else
                        <a href="{{ route('bookings.index') }}" wire:navigate class="btn-ghost-light bg-navy-900/40 px-7 py-3.5 text-base backdrop-blur-sm">My bookings</a>
                    @endguest
                </div>
            </div>
        </div>

        {{-- ============================================================
             SCOREBOARD: live numbers, overlapping the hero edge
             ============================================================ --}}
        <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
            <dl class="grid translate-y-1/2 grid-cols-2 overflow-hidden rounded-xl border border-line/10 bg-navy-800 shadow-float lg:grid-cols-4">
                @foreach ([
                    ['Courts free now', $stats['open_now'], '/'.$stats['courts']],
                    ['Open hours left today', $stats['free_hours'], null],
                    ['Rental paddles', $stats['paddles'], null],
                    ['Courts from', $stats['from_rate'] !== null ? '₱'.number_format($stats['from_rate'], 0) : '—', '/hr'],
                ] as $i => [$label, $value, $suffix])
                    <div @class([
                        'px-5 py-5 sm:px-7',
                        'border-l border-line/10' => $i % 2 === 1,
                        'lg:border-l lg:border-line/10' => $i === 2,
                        'border-t border-line/10 lg:border-t-0' => $i >= 2,
                    ])>
                        <dt class="text-sm font-medium text-line/60">{{ $label }}</dt>
                        <dd class="mt-2 flex items-baseline gap-1">
                            <span class="scoreboard-num text-5xl text-ball">{{ $value }}</span>
                            @if ($suffix)<span class="scoreboard-num text-2xl text-line/50">{{ $suffix }}</span>@endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ============================================================
         COURTS TODAY: a front-desk schedule board over an aerial
         photo of real courts. Every free hour links straight to booking.
         ============================================================ --}}
    {{-- top padding clears the overhanging scoreboard (2 rows on phones, 1 row on desktop) --}}
    <section id="courts" class="mx-auto max-w-6xl scroll-mt-20 px-4 pt-40 pb-20 sm:px-6 lg:pt-28">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="page-title text-[clamp(2rem,4vw,2.75rem)]">Courts today</h2>
                <p class="mt-2 text-lg text-navy-700">{{ today()->format('l, F j') }}@if ($opens && $closes), open {{ $short($opens) }} to {{ $short($closes) }}@endif. Tap a free hour to book it.</p>
            </div>
            <ul class="flex flex-wrap gap-4 text-sm text-navy-700" aria-label="Schedule key">
                <li class="flex items-center gap-1.5"><span class="h-4 w-5 rounded-sm bg-ball ring-1 ring-navy-900/20"></span>Free</li>
                <li class="flex items-center gap-1.5"><span class="hatched h-4 w-5 rounded-sm bg-brand/70"></span>Booked</li>
                <li class="flex items-center gap-1.5"><span class="h-4 w-5 rounded-sm bg-navy-700"></span>Past</li>
            </ul>
        </div>

        <div class="relative mt-8 overflow-hidden rounded-2xl bg-navy-900 text-line shadow-lift">
            {{-- aerial photo of real courts under a navy wash --}}
            <img src="{{ asset('images/courts-aerial.jpg') }}" alt="" width="2000" height="1125" loading="lazy" decoding="async"
                 class="pointer-events-none absolute inset-0 size-full object-cover" aria-hidden="true">
            <div class="pointer-events-none absolute inset-0 bg-navy-900/[0.88]" aria-hidden="true"></div>

            <div class="relative px-4 sm:px-6">
                @forelse ($courtStatus as $status)
                    @php($court = $status['court'])
                    @php($bookable = $status['next_free'] !== null)
                    <div wire:key="board-{{ $court->id }}" @class([
                        'grid items-center gap-x-6 gap-y-4 py-5 lg:grid-cols-[13rem_minmax(0,1fr)_9rem]',
                        'border-t border-line/10' => ! $loop->first,
                    ])>
                        {{-- court --}}
                        <div class="flex items-start justify-between gap-3 lg:block">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xl font-bold">{{ $court->name }}</h3>
                                    @if ($court->surface_type)
                                        <span class="rounded-full border border-line/20 px-2 py-0.5 text-xs font-semibold text-line/75">{{ $court->surface_type }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm">
                                    @if ($status['open_now'])
                                        <span class="inline-flex items-center gap-1.5 font-semibold text-ball"><span class="size-1.5 rounded-full bg-ball" aria-hidden="true"></span>Free right now</span>
                                    @elseif ($bookable)
                                        <span class="font-semibold text-ball">Next free {{ $short($status['next_free']) }}</span>
                                    @elseif ($status['closed'])
                                        <span class="text-line/55">Closed for today</span>
                                    @else
                                        <span class="text-line/55">Fully booked today</span>
                                    @endif
                                </p>
                            </div>
                            <p class="font-condensed text-2xl font-bold lg:mt-2">₱{{ number_format($court->hourly_rate, 0) }}<span class="text-sm font-medium text-line/60">/hr</span></p>
                        </div>

                        {{-- hours: free ones are links that open booking with this court and time preselected --}}
                        <div class="-mx-4 overflow-x-auto px-4 pb-1 sm:-mx-6 sm:px-6 lg:mx-0 lg:px-0">
                            <ol class="grid min-w-max auto-cols-[2.6rem] grid-flow-col gap-1 lg:min-w-0 lg:auto-cols-fr" aria-label="{{ $court->name }} hours today">
                                @foreach ($status['hours'] as $hour)
                                    @php($label = \Carbon\Carbon::parse($hour['start_time'])->format('ga'))
                                    {{-- phones skip hours that have passed so each row starts at the next playable hour --}}
                                    <li @class(['hidden lg:block' => $hour['past']])>
                                        @if ($hour['available'])
                                            <a href="{{ route('book', ['court' => $court->id, 'date' => today()->toDateString(), 'start' => $hour['start_time']]) }}" wire:navigate
                                               class="flex h-12 items-center justify-center rounded-md bg-ball font-condensed text-sm font-bold text-navy-900 transition-colors hover:bg-line focus-visible:outline-offset-1"
                                               aria-label="Book {{ $court->name }} at {{ $time($hour['start_time']) }}">{{ $label }}</a>
                                        @elseif ($hour['taken'] && ! $hour['past'])
                                            <span class="hatched flex h-12 items-center justify-center rounded-md bg-brand/70 font-condensed text-sm font-semibold text-navy-900/70" title="{{ $label }}: booked">{{ $label }}<span class="sr-only">, booked</span></span>
                                        @else
                                            <span class="flex h-12 items-center justify-center rounded-md bg-navy-800/80 font-condensed text-sm font-medium text-line/40" title="{{ $label }}: past">{{ $label }}<span class="sr-only">, past</span></span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        {{-- action --}}
                        <a href="{{ route('book', ['court' => $court->id]) }}" wire:navigate
                           class="{{ $bookable ? 'btn-accent' : 'btn-ghost-light' }} w-full">
                            {{ $bookable ? 'Book '.$court->name : 'Book another day' }}
                        </a>
                    </div>
                @empty
                    <p class="py-8 text-line/70">No courts are open for booking yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ============================================================
         HOW IT WORKS: three real photos, one per step (a true sequence,
         so the steps are numbered). Photos: public/images/step-*.jpg
         ============================================================ --}}
    <section class="border-y border-rule bg-white">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div>
                <h2 class="page-title text-[clamp(2rem,4vw,2.75rem)]">From booking to first serve</h2>
                <p class="mt-3 text-lg text-navy-700">Three steps, about a minute, and you're on court.</p>
            </div>

            <ol class="relative mt-12 grid gap-8 md:grid-cols-3">
                {{-- dashed rally line linking the steps on wide screens --}}
                <li class="pointer-events-none absolute top-[calc(37.5%*0.9)] right-[16%] left-[16%] hidden border-t-2 border-dashed border-navy-200 md:block" aria-hidden="true"></li>

                @foreach ([
                    ['step-court.jpg', 'An empty pickleball court with the net and lines in the sun', 'Pick a court and time', 'Free hours show in lime. Choose a start time and how long you want to play, up to '.config('booking.max_hours').' hours.'],
                    ['step-paddles.jpg', 'Pickleball paddles and balls on a court', 'Add paddles if you need them', 'Right after booking, rent paddles for the same time. You only see what is actually free for your slot.'],
                    ['step-play.jpg', 'Two players walking to the net during a night game', 'Show up and play', 'Your booking reference and total are saved under My bookings. Change or cancel any time before you start.'],
                ] as $i => [$photo, $alt, $heading, $text])
                    <li class="relative">
                        <figure class="relative overflow-hidden rounded-xl shadow-lift">
                            <img src="{{ asset('images/'.$photo) }}" alt="{{ $alt }}" width="1200" height="900" loading="lazy" decoding="async"
                                 class="aspect-[4/3] w-full object-cover">
                            {{-- soft navy fade so the step badge always reads --}}
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-navy-900/50 to-transparent" aria-hidden="true"></div>
                            <span class="absolute top-4 left-4 grid size-12 place-items-center rounded-lg bg-navy-900 shadow-float ring-2 ring-ball/80">
                                <span class="scoreboard-num text-3xl text-ball">{{ $i + 1 }}</span>
                                <span class="sr-only">Step {{ $i + 1 }}</span>
                            </span>
                        </figure>
                        <h3 class="mt-6 text-xl font-bold">{{ $heading }}</h3>
                        <p class="mt-2 leading-relaxed text-navy-700">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="mt-12 flex flex-wrap items-center gap-4">
                <a href="{{ route('book') }}" wire:navigate class="btn-accent px-7 py-3.5 text-base">Book a court</a>
                <p class="text-navy-700">No account yet? You can create one when you book.</p>
            </div>
        </div>
    </section>

    {{-- ============================================================
         PRO SHOP: rental paddles with real photos (uploaded in
         Admin > Paddles). Paddles without a photo get the drawing.
         ============================================================ --}}
    @if ($paddles->isNotEmpty())
        @php($palette = [
            ['var(--color-brand)', 'var(--color-navy-900)', 'var(--color-navy-900)'],
            ['var(--color-ball)', 'var(--color-navy-900)', 'var(--color-navy-700)'],
            ['var(--color-navy-600)', 'var(--color-brand)', 'var(--color-navy-900)'],
        ])
        <section id="paddles" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="max-w-xl">
                    <h2 class="page-title text-[clamp(2rem,4vw,2.75rem)]">Rental paddles</h2>
                    <p class="mt-3 text-lg leading-relaxed text-navy-700">Forgot yours? Add paddles right after you book. They're priced per hour and held for exactly your time on court.</p>
                </div>
                <a href="{{ route('book') }}" wire:navigate class="btn-primary px-6 py-3 text-base">Book a court first</a>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($paddles as $i => $paddle)
                    <article wire:key="shop-{{ $paddle->id }}" class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-lift ring-1 ring-rule">
                        <div class="relative aspect-[4/3] overflow-hidden bg-concrete">
                            @if ($paddle->image_url)
                                <img src="{{ $paddle->image_url }}" alt="{{ $paddle->name }}" width="1000" height="750" loading="lazy"
                                     class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
                            @else
                                @php([$face, $edge, $grip] = $palette[$i % count($palette)])
                                <div class="perforated grid size-full place-items-center">
                                    <x-paddle-art :face="$face" :edge="$edge" :grip="$grip" class="h-[70%] w-auto -rotate-12 drop-shadow-[0_14px_14px_rgb(16_28_44/0.3)]" />
                                </div>
                            @endif
                            {{-- price tag on the photo --}}
                            <span class="absolute bottom-3 left-3 rounded-lg bg-navy-900/90 px-3 py-1.5 text-line shadow-float backdrop-blur-sm">
                                <span class="font-condensed text-2xl font-bold">₱{{ number_format($paddle->hourly_rate, 0) }}</span><span class="text-sm text-line/70">/hr each</span>
                            </span>
                            <span class="absolute top-3 right-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-navy-800 shadow-sm">{{ $paddle->stock_quantity }} in the club</span>
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            @if ($paddle->brand)
                                <p class="text-sm font-semibold text-navy-600">{{ $paddle->brand }}</p>
                            @endif
                            <h3 class="text-xl font-bold">{{ $paddle->name }}</h3>
                            @if ($paddle->description)
                                <p class="mt-1 text-navy-700">{{ $paddle->description }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============================================================
         CLOSING CALL TO ACTION: club orange on the left, a real
         player on the right.
         ============================================================ --}}
    <section class="mx-auto max-w-6xl px-4 pb-20 sm:px-6">
        <div class="relative isolate overflow-hidden rounded-2xl bg-brand">
            {{-- photo fills the right side and fades into the orange --}}
            <img src="{{ asset('images/cta-player.jpg') }}" alt="" width="1000" height="1500" loading="lazy"
                 class="absolute inset-y-0 right-0 -z-10 h-full w-full object-cover object-[50%_30%] sm:w-[58%]" aria-hidden="true">
            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-brand from-35% via-brand/85 via-50% to-brand/10 sm:from-40% sm:via-55%" aria-hidden="true"></div>
            <div class="perforated absolute inset-y-0 left-0 -z-10 w-full opacity-60 [mask-image:linear-gradient(to_right,black_30%,transparent_60%)]" aria-hidden="true"></div>

            <div class="max-w-lg px-6 py-14 sm:px-12 sm:py-16">
                <h2 class="font-display text-[clamp(2rem,4.5vw,3rem)] leading-none text-navy-900">Your court is waiting</h2>
                <p class="mt-4 text-lg text-navy-900/85">Pick an hour, add paddles, and you're set. It takes less than a minute.</p>
                <div class="mt-7 flex flex-wrap items-center gap-4">
                    <a href="{{ route('book') }}" wire:navigate class="btn bg-navy-900 px-7 py-3.5 text-base text-line hover:bg-navy-800">Book a court</a>
                    @if ($stats['open_now'] > 0)
                        <span class="inline-flex items-center gap-2 font-semibold text-navy-900">
                            <span class="size-2.5 rounded-full bg-navy-900" aria-hidden="true"></span>{{ $stats['open_now'] }} {{ Str::plural('court', $stats['open_now']) }} free right now
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
