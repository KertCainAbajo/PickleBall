<div>
    @php($time = fn ($t) => \Carbon\Carbon::parse($t)->format('g:i A'))

    {{-- Hero: the night court, with today's courts drawn from above --}}
    <section class="court-surface text-line">
        <div class="mx-auto max-w-6xl px-4 pt-14 pb-16 sm:px-6">
            <div class="flex items-end justify-between gap-10">
                <div>
                    <h1 class="font-display text-[clamp(2.5rem,6.5vw,4.5rem)] leading-[0.98]">
                        Book a court.<br>Grab a paddle.<br>Play.
                    </h1>
                    <p class="mt-6 max-w-md text-lg leading-relaxed text-line/75">
                        Pick an open hour on one of our {{ $courtStatus->count() }} courts, then add rental paddles before you check out.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('book') }}" wire:navigate class="btn-accent px-6 py-3 text-base">Book a court</a>
                        @guest
                            <a href="{{ route('register') }}" wire:navigate class="btn-ghost-light px-6 py-3 text-base">Create an account</a>
                        @endguest
                    </div>
                </div>
                <img src="{{ asset('images/logo-md.png') }}" alt="" class="hidden w-60 shrink-0 lg:block" width="450" height="480">
            </div>

            <div class="mt-16 flex flex-wrap items-baseline justify-between gap-2 border-b border-line/15 pb-3">
                <h2 class="text-lg font-semibold">Courts today, {{ today()->format('l, F j') }}</h2>
                @if ($opens && $closes)
                    <p class="text-sm text-line/60">Open {{ $time($opens) }} to {{ $time($closes) }}</p>
                @endif
            </div>

            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:gap-x-6 lg:grid-cols-4">
                @forelse ($courtStatus as $status)
                    @php($court = $status['court'])
                    @php($open = $status['next_free'] !== null)
                    <a href="{{ route('book', ['court' => $court->id]) }}" wire:navigate wire:key="today-{{ $court->id }}"
                       class="group block rounded-sm focus-visible:outline-offset-4">
                        <x-court-diagram
                            :surface="$open ? 'var(--color-navy-700)' : 'var(--color-navy-800)'"
                            :lines="$open ? 'var(--color-line)' : 'rgb(244 245 247 / 0.35)'"
                            :kitchen="$open ? 'rgb(242 102 9 / 0.85)' : null"
                            :net="$open ? 'var(--color-line)' : 'rgb(244 245 247 / 0.35)'"
                            class="rounded-sm" />

                        <div class="mt-3 flex flex-wrap items-baseline justify-between gap-x-2">
                            <span class="text-lg font-semibold group-hover:underline group-hover:decoration-brand group-hover:underline-offset-4">{{ $court->name }}</span>
                            <span class="font-condensed text-lg font-semibold">₱{{ number_format($court->hourly_rate, 0) }}<span class="text-sm font-medium text-line/60">/hr</span></span>
                        </div>
                        @if ($court->surface_type)
                            <p class="text-sm text-line/60">{{ $court->surface_type }}</p>
                        @endif

                        <p class="mt-2 font-condensed text-base font-semibold">
                            @if ($open)
                                <span class="text-ball">Next free {{ $time($status['next_free']) }}</span>
                                <span class="block text-sm font-medium text-line/60">{{ $status['free_hours'] }} open {{ Str::plural('hour', $status['free_hours']) }} left today</span>
                            @elseif ($status['closed'])
                                <span class="text-line/50">Closed for today</span>
                            @else
                                <span class="text-line/50">Fully booked today</span>
                            @endif
                        </p>
                    </a>
                @empty
                    <p class="text-line/70">No courts are open for booking yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Rental counter --}}
    @if ($paddles->isNotEmpty())
        <section class="page">
            <div class="grid gap-8 md:grid-cols-[18rem_1fr]">
                <div>
                    <h2 class="page-title text-3xl">Rental paddles</h2>
                    <p class="mt-3 text-navy-700">Forgot yours? Add paddles right after you book a court. They're priced per hour, for the same time as your booking.</p>
                </div>

                <ul class="divide-y divide-rule border-y border-rule">
                    @foreach ($paddles as $paddle)
                        <li class="grid grid-cols-[1fr_auto] items-baseline gap-x-6 gap-y-1 py-4">
                            <div>
                                <p class="font-semibold text-navy-900">{{ $paddle->name }}
                                    @if ($paddle->brand)
                                        <span class="font-normal text-navy-600">by {{ $paddle->brand }}</span>
                                    @endif
                                </p>
                                @if ($paddle->description)
                                    <p class="text-sm text-navy-600">{{ $paddle->description }}</p>
                                @endif
                            </div>
                            <p class="font-condensed text-xl font-semibold text-navy-900">₱{{ number_format($paddle->hourly_rate, 0) }}<span class="text-sm font-medium text-navy-600">/hr each</span></p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</div>
