<div class="space-y-12">
    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-3xl bg-court-800 px-6 py-14 text-white sm:px-12">
        <div class="pointer-events-none absolute -top-16 -right-16 size-64 rounded-full bg-ball/20 blur-2xl"></div>
        <div class="relative max-w-xl">
            <p class="mb-3 inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold tracking-wide text-ball uppercase">Open daily · 6 AM – 10 PM</p>
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Book your court. Grab a paddle. Play.</h1>
            <p class="mt-4 text-lg text-white/80">Reserve a pickleball court in seconds and rent paddles right after booking — no need to bring your own.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('book') }}" wire:navigate class="btn-accent px-6 py-3 text-base">Book a court</a>
                @guest
                    <a href="{{ route('register') }}" wire:navigate class="btn border border-white/30 px-6 py-3 text-base text-white hover:bg-white/10">Create an account</a>
                @endguest
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['1', 'Pick a court & time', 'See which hours are free and choose how long you want to play.'],
            ['2', 'Rent paddles (optional)', 'Right after booking, add paddles for the same time slot.'],
            ['3', 'Show up & play', 'Your booking reference and total are saved under My Bookings.'],
        ] as [$step, $heading, $text])
            <div class="card p-5">
                <span class="grid size-8 place-items-center rounded-full bg-court-100 text-sm font-bold text-court-700">{{ $step }}</span>
                <h3 class="mt-3 font-semibold">{{ $heading }}</h3>
                <p class="mt-1 text-sm text-zinc-600">{{ $text }}</p>
            </div>
        @endforeach
    </section>

    {{-- Courts --}}
    <section>
        <h2 class="text-2xl font-bold tracking-tight">Our courts</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($courts as $court)
                <div class="card flex flex-col p-5">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold">{{ $court->name }}</h3>
                        @if ($court->surface_type)
                            <span class="badge bg-court-50 text-court-700">{{ $court->surface_type }}</span>
                        @endif
                    </div>
                    <p class="mt-2 flex-1 text-sm text-zinc-600">{{ $court->description }}</p>
                    <p class="mt-4 text-lg font-bold">₱{{ number_format($court->hourly_rate, 2) }}<span class="text-sm font-normal text-zinc-500"> / hour</span></p>
                    <a href="{{ route('book', ['court' => $court->id]) }}" wire:navigate class="btn-primary mt-3">Book this court</a>
                </div>
            @empty
                <p class="text-zinc-500">No courts available yet.</p>
            @endforelse
        </div>
    </section>

    {{-- Paddles --}}
    @if ($paddles->isNotEmpty())
        <section>
            <h2 class="text-2xl font-bold tracking-tight">Paddles for rent</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach ($paddles as $paddle)
                    <div class="card p-5">
                        <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">{{ $paddle->brand }}</p>
                        <h3 class="font-semibold">{{ $paddle->name }}</h3>
                        <p class="mt-1 text-sm text-zinc-600">{{ $paddle->description }}</p>
                        <p class="mt-3 font-bold">₱{{ number_format($paddle->hourly_rate, 2) }}<span class="text-sm font-normal text-zinc-500"> / hour each</span></p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
