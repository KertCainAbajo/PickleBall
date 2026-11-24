<div>
    @php $fmt = fn ($t, $f = 'g:i A') => \Carbon\Carbon::parse($t)->format($f); @endphp

    <x-page-header title="Book a court" subtitle="Pick a court, a day and your start time. Paddles come next." :image="$court?->image_path">
        <x-booking-steps :current="1" />
    </x-page-header>

    <div class="page">
        @if ($courts->isEmpty())
            <p class="card p-8 text-navy-700">No courts are open for booking right now. Check back soon.</p>
        @else
            <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_23rem]">
                {{-- min-w-0: lets the scrolling day strip scroll instead of widening the page --}}
                <div class="min-w-0 space-y-12">

                    {{-- ── Court ─────────────────────────────────────────── --}}
                    <section aria-labelledby="pick-court">
                        <h2 id="pick-court" class="mb-4 text-xl font-bold">Choose a court</h2>
                        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4" role="radiogroup" aria-labelledby="pick-court">
                            @foreach ($courts as $option)
                                @php $isSelected = $option->id === $courtId; @endphp
                                <button type="button" role="radio" aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                                        wire:click="selectCourt({{ $option->id }})" wire:key="court-{{ $option->id }}"
                                        @class([
                                            'group cursor-pointer overflow-hidden rounded-xl bg-white text-left transition-shadow',
                                            'shadow-lift ring-[3px] ring-brand' => $isSelected,
                                            'ring-1 ring-rule hover:shadow-lift hover:ring-navy-300' => ! $isSelected,
                                        ])>
                                    <div class="relative aspect-[4/3] overflow-hidden bg-navy-900">
                                        @if ($option->image_url)
                                            <img src="{{ $option->image_url }}" alt="" class="size-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                                        @else
                                            <div class="grid size-full place-items-center p-3"><x-court-diagram class="rounded-sm" /></div>
                                        @endif
                                        <span class="absolute bottom-2 left-2 rounded-md bg-navy-900/90 px-2 py-1 font-condensed text-base font-bold text-line">
                                            ₱{{ number_format($option->hourly_rate, 0) }}<span class="text-xs font-medium text-line/70">/hr</span>
                                        </span>
                                        @if ($isSelected)
                                            <span class="absolute top-2 right-2 grid size-7 place-items-center rounded-full bg-brand text-navy-900 shadow-float" aria-hidden="true">
                                                <svg viewBox="0 0 16 16" class="size-4" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 8.5 6.5 12 13 4.5"/></svg>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="px-3.5 py-3">
                                        <p class="font-bold">{{ $option->name }}</p>
                                        <p class="text-sm text-navy-600">{{ $option->surface_type ?: 'Court' }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </section>

                    {{-- ── Day ───────────────────────────────────────────── --}}
                    <section aria-labelledby="pick-day">
                        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
                            <h2 id="pick-day" class="text-xl font-bold">Choose a day</h2>
                            <label class="flex items-center gap-2 text-sm font-medium text-navy-700">
                                Another day
                                <input type="date" wire:model.live="date" class="input w-auto py-1.5"
                                       min="{{ today()->toDateString() }}" max="{{ $maxDate }}">
                            </label>
                        </div>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            @foreach ($quickDates as $day)
                                @php $value = $day->toDateString(); @endphp
                                @php $isDay = $value === $date; @endphp
                                <button type="button" wire:click="selectDate('{{ $value }}')" wire:key="day-{{ $value }}"
                                        aria-pressed="{{ $isDay ? 'true' : 'false' }}"
                                        @class([
                                            'flex w-[4.75rem] shrink-0 cursor-pointer flex-col items-center rounded-xl py-3 transition-colors',
                                            'bg-navy-900 text-line shadow-lift' => $isDay,
                                            'bg-white ring-1 ring-rule hover:ring-navy-300' => ! $isDay,
                                        ])>
                                    <span @class(['text-sm font-semibold', 'text-brand' => $isDay, 'text-navy-600' => ! $isDay])>{{ $day->isToday() ? 'Today' : $day->format('D') }}</span>
                                    <span class="font-condensed text-3xl leading-none font-bold">{{ $day->format('j') }}</span>
                                    <span @class(['text-sm', 'text-line/70' => $isDay, 'text-navy-600' => ! $isDay])>{{ $day->format('M') }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>

                    {{-- ── Start time, grouped by part of the day ──────────── --}}
                    <section aria-labelledby="pick-time">
                        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
                            <h2 id="pick-time" class="text-xl font-bold">Choose a start time</h2>
                            <ul class="flex flex-wrap gap-4 text-sm text-navy-700" aria-label="Legend">
                                <li class="flex items-center gap-1.5"><span class="h-4 w-3 rounded-sm border-l-4 border-ball-dark bg-white ring-1 ring-rule"></span>Free</li>
                                <li class="flex items-center gap-1.5"><span class="h-4 w-3 rounded-sm border-l-4 border-brand bg-navy-700"></span>Your time</li>
                                <li class="flex items-center gap-1.5"><span class="hatched h-4 w-3 rounded-sm bg-brand-100"></span>Booked</li>
                                <li class="flex items-center gap-1.5"><span class="h-4 w-3 rounded-sm bg-navy-100"></span>Past</li>
                            </ul>
                        </div>

                        <div class="relative">
                            <div wire:loading.flex wire:target="selectCourt,selectDate,date"
                                 class="absolute inset-0 z-10 items-center justify-center rounded-xl bg-concrete/80 font-semibold text-navy-700">
                                Loading times
                            </div>

                            @if (empty($timeSlots))
                                <p class="rounded-xl bg-white p-6 text-navy-700 ring-1 ring-rule">This court has no bookable hours on that day.</p>
                            @else
                                @php
                                    $groups = [
                                        'Morning' => fn ($h) => $h < 12,
                                        'Afternoon' => fn ($h) => $h >= 12 && $h < 17,
                                        'Evening' => fn ($h) => $h >= 17,
                                    ];
                                @endphp
                                <div class="space-y-6">
                                    @foreach ($groups as $part => $inPart)
                                        @php
                                            $hours = collect($timeSlots)->filter(fn ($s) => $inPart((int) substr($s['start_time'], 0, 2)))->values()->all();
                                            $freeCount = collect($hours)->where('available', true)->count();
                                            $allPast = collect($hours)->every(fn ($s) => $s['past']);
                                        @endphp
                                        @continue(empty($hours))

                                        <div wire:key="part-{{ $part }}">
                                            <div class="mb-2.5 flex items-baseline justify-between gap-3">
                                                <h3 class="font-semibold">{{ $part }}</h3>
                                                <p class="text-sm text-navy-600">
                                                    @if ($allPast) Already passed @else {{ $freeCount }} free @endif
                                                </p>
                                            </div>

                                            @unless ($allPast)
                                                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                                                    @foreach ($hours as $slot)
                                                        @php $isMine = in_array($slot['start_time'], $selectedStarts, true); @endphp
                                                        @php $isBooked = $slot['taken'] && ! $slot['past']; @endphp
                                                        <button type="button" wire:key="slot-{{ $slot['start_time'] }}"
                                                                wire:click="selectSlot('{{ $slot['start_time'] }}')"
                                                                @disabled(! $slot['available'])
                                                                aria-pressed="{{ $isMine ? 'true' : 'false' }}"
                                                                aria-label="{{ $fmt($slot['start_time']) }}{{ $slot['available'] ? '' : ($isBooked ? ', booked' : ', past') }}"
                                                                @class([
                                                                    'relative rounded-lg border-l-4 py-2.5 pr-2 pl-3 text-left transition-colors',
                                                                    'cursor-pointer border-brand bg-navy-700 text-line shadow-lift' => $isMine,
                                                                    'cursor-pointer border-ball-dark bg-white ring-1 ring-rule hover:ring-2 hover:ring-navy-700' => $slot['available'] && ! $isMine,
                                                                    'hatched cursor-not-allowed border-brand/50 bg-brand-50 text-navy-600' => $isBooked,
                                                                    'cursor-not-allowed border-transparent bg-navy-100/70 text-navy-300' => $slot['past'],
                                                                ])>
                                                            <span class="font-condensed text-xl leading-none font-bold">{{ $fmt($slot['start_time'], 'g:i') }}</span>
                                                            <span class="text-sm font-medium">{{ $fmt($slot['start_time'], 'A') }}</span>
                                                            @if ($isBooked)
                                                                <span class="block text-xs font-semibold text-brand-dark">Booked</span>
                                                            @elseif ($slot['past'])
                                                                <span class="block text-xs font-medium">Past</span>
                                                            @endif
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endunless
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-4 text-sm text-navy-600">To play longer, pick the next free hour after your start time, or set the length on your booking.</p>
                            @endif
                        </div>
                    </section>
                </div>

                {{-- ── Booking ticket ─────────────────────────────────── --}}
                <aside class="lg:sticky lg:top-24" aria-labelledby="ticket-title">
                    <div class="ticket overflow-hidden">
                        <div class="relative isolate h-36 bg-navy-900 text-line">
                            @if ($court?->image_url)
                                <img src="{{ $court->image_url }}" alt="" class="absolute inset-0 -z-10 size-full object-cover" aria-hidden="true">
                            @endif
                            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-navy-900 via-navy-900/60 to-navy-900/10" aria-hidden="true"></div>
                            <div class="absolute inset-x-5 bottom-4">
                                <h2 id="ticket-title" class="text-sm font-semibold text-line/75">Your booking</h2>
                                <p class="font-display text-2xl leading-tight">{{ $court?->name ?? 'Choose a court' }}</p>
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 gap-x-4 gap-y-4 px-5 py-5">
                            <div>
                                <dt class="text-sm text-navy-600">Day</dt>
                                <dd class="font-semibold">{{ $fmt($date, 'D, M j') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-navy-600">Time</dt>
                                <dd class="font-semibold">
                                    @if ($startTime)
                                        {{ $fmt($startTime) }} to {{ $fmt($endTime) }}
                                    @else
                                        <span class="font-normal text-navy-600">Not chosen yet</span>
                                    @endif
                                </dd>
                            </div>

                            @if ($startTime)
                                <div class="col-span-2">
                                    <dt class="mb-2 text-sm text-navy-600">How long</dt>
                                    <dd class="flex flex-wrap gap-2">
                                        @for ($h = 1; $h <= $maxDuration; $h++)
                                            <button type="button" wire:click="setDuration({{ $h }})" wire:key="dur-{{ $h }}"
                                                    aria-pressed="{{ $h === $duration ? 'true' : 'false' }}"
                                                    @class([
                                                        'cursor-pointer rounded-lg px-3.5 py-1.5 font-condensed text-base font-bold transition-colors',
                                                        'bg-navy-700 text-line' => $h === $duration,
                                                        'bg-white ring-1 ring-rule hover:ring-navy-700' => $h !== $duration,
                                                    ])>
                                                {{ $h }} {{ Str::plural('hr', $h) }}
                                            </button>
                                        @endfor
                                    </dd>
                                </div>
                            @endif

                            <div class="col-span-2">
                                <dt><label for="notes" class="text-sm text-navy-600">Note for the front desk (optional)</label></dt>
                                <dd class="mt-1.5">
                                    <textarea wire:model="notes" id="notes" rows="2" class="input" placeholder="For example: group of 4, first time playing"></textarea>
                                    @error('notes') <p class="error">{{ $message }}</p> @enderror
                                </dd>
                            </div>
                        </dl>

                        <div class="ticket-tear"></div>

                        <div class="px-5 pt-4 pb-5">
                            <div class="flex items-baseline justify-between">
                                <span class="font-semibold">Court fee</span>
                                <span class="font-condensed text-4xl leading-none font-bold">₱{{ number_format($total, 2) }}</span>
                            </div>
                            @if ($startTime && $court)
                                <p class="mt-1 text-right text-sm text-navy-600">₱{{ number_format($court->hourly_rate, 0) }} × {{ $duration }} {{ Str::plural('hour', $duration) }}</p>
                            @endif

                            @if ($errors->any())
                                <p class="mt-4 rounded-md border-l-4 border-red-700 bg-red-50 px-3 py-2 text-sm font-medium text-red-800" role="alert">{{ $errors->first() }}</p>
                            @endif

                            @if (! $startTime)
                                <button type="button" class="btn mt-4 w-full cursor-not-allowed bg-navy-100 py-3 text-base text-navy-600" disabled>Choose a start time</button>
                            @else
                                <button type="button" wire:click="book" class="btn-accent mt-4 w-full py-3 text-base"
                                        wire:loading.attr="disabled" wire:target="book">
                                    <span wire:loading.remove wire:target="book">{{ auth()->check() ? 'Book '.$court?->name : 'Log in to book' }}</span>
                                    <span wire:loading wire:target="book">Booking</span>
                                </button>
                            @endif
                            <p class="mt-2.5 text-center text-sm text-navy-600">Next: add rental paddles if you need them.</p>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</div>
