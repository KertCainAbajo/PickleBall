<div class="page">
    @php($fmt = fn ($t, $f = 'g:i A') => \Carbon\Carbon::parse($t)->format($f))

    <header class="mb-8 max-w-2xl">
        <h1 class="page-title text-4xl">Book a court</h1>
        <p class="mt-3 text-navy-700">Choose a court, a day and when you want to start. You can add rental paddles on the next step.</p>
    </header>

    @if ($courts->isEmpty())
        <p class="card p-8 text-navy-700">No courts are open for booking right now. Check back soon.</p>
    @else
        <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            {{-- min-w-0: lets the scrolling day strip scroll instead of widening the page --}}
            <div class="min-w-0 space-y-10">
                {{-- Court --}}
                <section aria-labelledby="pick-court">
                    <h2 id="pick-court" class="mb-4 text-lg font-semibold">Court</h2>
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4" role="radiogroup" aria-labelledby="pick-court">
                        @foreach ($courts as $option)
                            @php($isSelected = $option->id === $courtId)
                            <button type="button" role="radio" aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                                    wire:click="selectCourt({{ $option->id }})" wire:key="court-{{ $option->id }}"
                                    @class([
                                        'group cursor-pointer rounded-md p-2 text-left transition-colors',
                                        'bg-navy-900 ring-2 ring-brand' => $isSelected,
                                        'bg-white ring-1 ring-rule hover:ring-navy-300' => ! $isSelected,
                                    ])>
                                <x-court-diagram
                                    :surface="$isSelected ? 'var(--color-navy-700)' : 'var(--color-navy-100)'"
                                    :lines="$isSelected ? 'var(--color-line)' : 'var(--color-white, #fff)'"
                                    :kitchen="$isSelected ? 'rgb(242 102 9 / 0.85)' : 'var(--color-navy-200)'"
                                    :net="$isSelected ? 'var(--color-line)' : 'var(--color-navy-300)'"
                                    class="rounded-sm" />
                                <span class="mt-2 flex items-baseline justify-between gap-2 px-1">
                                    <span @class(['font-semibold', 'text-line' => $isSelected])>{{ $option->name }}</span>
                                    <span @class(['font-condensed font-semibold', 'text-line' => $isSelected, 'text-navy-700' => ! $isSelected])>₱{{ number_format($option->hourly_rate, 0) }}/hr</span>
                                </span>
                                <span @class(['block px-1 text-sm', 'text-line/60' => $isSelected, 'text-navy-600' => ! $isSelected])>
                                    {{ $option->surface_type ?: 'Court' }}, {{ $fmt($option->opening_time, 'g A') }} to {{ $fmt($option->closing_time, 'g A') }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </section>

                {{-- Day --}}
                <section aria-labelledby="pick-day">
                    <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
                        <h2 id="pick-day" class="text-lg font-semibold">Day</h2>
                        <label class="flex items-center gap-2 text-sm text-navy-700">
                            Another day
                            <input type="date" wire:model.live="date" class="input w-auto py-1.5"
                                   min="{{ today()->toDateString() }}" max="{{ $maxDate }}">
                        </label>
                    </div>
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        @foreach ($quickDates as $day)
                            @php($value = $day->toDateString())
                            <button type="button" wire:click="selectDate('{{ $value }}')" wire:key="day-{{ $value }}"
                                    aria-pressed="{{ $value === $date ? 'true' : 'false' }}"
                                    @class([
                                        'flex w-[4.5rem] shrink-0 cursor-pointer flex-col items-center rounded-md py-2.5 transition-colors',
                                        'bg-navy-900 text-line' => $value === $date,
                                        'bg-white ring-1 ring-rule hover:ring-navy-300' => $value !== $date,
                                    ])>
                                <span class="text-sm font-medium opacity-75">{{ $day->isToday() ? 'Today' : $day->format('D') }}</span>
                                <span class="font-condensed text-3xl leading-none font-semibold">{{ $day->format('j') }}</span>
                                <span class="text-sm opacity-75">{{ $day->format('M') }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>

                {{-- Start time --}}
                <section aria-labelledby="pick-time">
                    <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
                        <h2 id="pick-time" class="text-lg font-semibold">Start time</h2>
                        <ul class="flex flex-wrap gap-4 text-sm text-navy-700" aria-label="Legend">
                            <li class="flex items-center gap-1.5"><span class="h-4 w-3 rounded-sm border-l-4 border-ball-dark bg-white ring-1 ring-rule"></span>Free</li>
                            <li class="flex items-center gap-1.5"><span class="h-4 w-3 rounded-sm border-l-4 border-brand bg-navy-700"></span>Your time</li>
                            <li class="flex items-center gap-1.5"><span class="hatched h-4 w-3 rounded-sm bg-navy-50 ring-1 ring-rule"></span>Taken</li>
                        </ul>
                    </div>

                    <div class="relative">
                        <div wire:loading.flex wire:target="selectCourt,selectDate,date"
                             class="absolute inset-0 z-10 items-center justify-center rounded-md bg-concrete/80 font-medium text-navy-700">
                            Loading times
                        </div>

                        @if (empty($timeSlots))
                            <p class="text-navy-700">This court has no bookable hours on that day.</p>
                        @else
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                                @foreach ($timeSlots as $slot)
                                    @php($isMine = in_array($slot['start_time'], $selectedStarts, true))
                                    <button type="button" wire:key="slot-{{ $slot['start_time'] }}"
                                            wire:click="selectSlot('{{ $slot['start_time'] }}')"
                                            @disabled(! $slot['available'])
                                            aria-pressed="{{ $isMine ? 'true' : 'false' }}"
                                            aria-label="{{ $fmt($slot['start_time']) }}{{ $slot['available'] ? '' : ', taken' }}"
                                            @class([
                                                'rounded-sm border-l-4 py-2.5 pr-2 pl-3 text-left transition-colors',
                                                'cursor-pointer border-brand bg-navy-700 text-line' => $isMine,
                                                'cursor-pointer border-ball-dark bg-white ring-1 ring-rule hover:ring-navy-700' => $slot['available'] && ! $isMine,
                                                'hatched cursor-not-allowed border-transparent bg-navy-50 text-navy-300' => ! $slot['available'],
                                            ])>
                                        <span class="font-condensed text-xl leading-none font-semibold">{{ $fmt($slot['start_time'], 'g:i') }}</span>
                                        <span class="text-sm font-medium">{{ $fmt($slot['start_time'], 'A') }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-3 text-sm text-navy-600">To play longer, choose the next free hour after your start time, or set the length on your ticket.</p>
                        @endif
                    </div>
                </section>
            </div>

            {{-- Ticket --}}
            <aside class="lg:sticky lg:top-6" aria-labelledby="ticket-title">
                <div class="ticket">
                    <div class="rounded-t-xl bg-navy-900 px-5 pt-4 pb-5 text-line">
                        <h2 id="ticket-title" class="font-display text-xl">Your booking</h2>
                        <p class="mt-1 text-sm text-line/60">{{ $court?->name ?? 'No court selected' }}</p>
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
                                    <span class="font-normal text-navy-600">Choose a start time</span>
                                @endif
                            </dd>
                        </div>

                        @if ($startTime)
                            <div class="col-span-2">
                                <dt class="mb-2 text-sm text-navy-600">Length</dt>
                                <dd class="flex flex-wrap gap-2">
                                    @for ($h = 1; $h <= $maxDuration; $h++)
                                        <button type="button" wire:click="setDuration({{ $h }})" wire:key="dur-{{ $h }}"
                                                aria-pressed="{{ $h === $duration ? 'true' : 'false' }}"
                                                @class([
                                                    'cursor-pointer rounded-sm px-3 py-1.5 font-condensed text-base font-semibold transition-colors',
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

                    <div class="ticket-tear mx-0"></div>

                    <div class="px-5 pt-4 pb-5">
                        <div class="flex items-baseline justify-between">
                            <span class="font-semibold">Court fee</span>
                            <span class="font-condensed text-4xl leading-none font-semibold">₱{{ number_format($total, 2) }}</span>
                        </div>
                        @if ($startTime && $court)
                            <p class="mt-1 text-right text-sm text-navy-600">₱{{ number_format($court->hourly_rate, 0) }} for each of {{ $duration }} {{ Str::plural('hour', $duration) }}</p>
                        @endif

                        @if ($errors->any())
                            <p class="mt-4 rounded-sm border-l-4 border-red-700 bg-red-50 px-3 py-2 text-sm font-medium text-red-800" role="alert">{{ $errors->first() }}</p>
                        @endif

                        <button type="button" wire:click="book" class="btn-accent mt-4 w-full py-3 text-base"
                                @disabled(! $startTime) wire:loading.attr="disabled" wire:target="book">
                            <span wire:loading.remove wire:target="book">{{ auth()->check() ? 'Book this court' : 'Log in to book' }}</span>
                            <span wire:loading wire:target="book">Booking</span>
                        </button>
                        <p class="mt-2 text-center text-sm text-navy-600">You can add rental paddles next.</p>
                    </div>
                </div>
            </aside>
        </div>
    @endif
</div>
