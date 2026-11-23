<div>
    <div class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight">Book a court</h1>
        <p class="mt-1 text-zinc-600">Choose a court, a date and your time. You can rent paddles on the next step.</p>
    </div>

    @if ($courts->isEmpty())
        <div class="card p-8 text-center text-zinc-600">No courts are open for booking right now.</div>
    @else
        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-6">
                {{-- 1. Court --}}
                <section class="card p-5">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-zinc-500 uppercase">1 · Court</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($courts as $option)
                            <button type="button" wire:click="selectCourt({{ $option->id }})" wire:key="court-{{ $option->id }}"
                                @class([
                                    'rounded-xl border-2 p-4 text-left transition cursor-pointer',
                                    'border-court-700 bg-court-50' => $option->id === $courtId,
                                    'border-zinc-200 hover:border-zinc-300' => $option->id !== $courtId,
                                ])>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold">{{ $option->name }}</span>
                                    <span class="text-sm font-bold text-court-700">₱{{ number_format($option->hourly_rate, 0) }}/hr</span>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ $option->surface_type }} · {{ \Carbon\Carbon::parse($option->opening_time)->format('g A') }}–{{ \Carbon\Carbon::parse($option->closing_time)->format('g A') }}
                                </p>
                            </button>
                        @endforeach
                    </div>
                </section>

                {{-- 2. Date --}}
                <section class="card p-5">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-zinc-500 uppercase">2 · Date</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($quickDates as $day)
                            @php $value = $day->toDateString(); @endphp
                            <button type="button" wire:click="selectDate('{{ $value }}')" wire:key="day-{{ $value }}"
                                @class([
                                    'flex w-16 cursor-pointer flex-col items-center rounded-xl border-2 py-2 transition',
                                    'border-court-700 bg-court-700 text-white' => $value === $date,
                                    'border-zinc-200 hover:border-zinc-300' => $value !== $date,
                                ])>
                                <span class="text-[11px] font-semibold uppercase opacity-80">{{ $day->isToday() ? 'Today' : $day->format('D') }}</span>
                                <span class="text-lg leading-tight font-bold">{{ $day->format('j') }}</span>
                                <span class="text-[11px] opacity-80">{{ $day->format('M') }}</span>
                            </button>
                        @endforeach
                        <label class="ml-1 text-sm text-zinc-500">
                            <span class="sr-only">Other date</span>
                            <input type="date" wire:model.live="date" class="input w-auto"
                                   min="{{ today()->toDateString() }}" max="{{ today()->addDays(\App\Livewire\BookCourt::DAYS_AHEAD)->toDateString() }}">
                        </label>
                    </div>
                </section>

                {{-- 3. Time --}}
                <section class="card p-5">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold tracking-wide text-zinc-500 uppercase">3 · Start time</h2>
                        <div class="flex items-center gap-3 text-xs text-zinc-500">
                            <span class="flex items-center gap-1"><span class="size-3 rounded border border-zinc-300 bg-white"></span>Free</span>
                            <span class="flex items-center gap-1"><span class="size-3 rounded bg-court-700"></span>Selected</span>
                            <span class="flex items-center gap-1"><span class="size-3 rounded bg-zinc-200"></span>Taken</span>
                        </div>
                    </div>

                    <div class="relative">
                        <div wire:loading.flex wire:target="selectCourt,selectDate,date"
                             class="absolute inset-0 z-10 items-center justify-center rounded-xl bg-white/70 text-sm font-medium text-zinc-600">
                            Loading times…
                        </div>

                        @if (empty($slots))
                            <p class="text-sm text-zinc-500">No time slots for this day.</p>
                        @else
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                                @foreach ($slots as $slot)
                                    @php $selected = in_array($slot['start_time'], $selectedStarts, true); @endphp
                                    <button type="button" wire:key="slot-{{ $slot['start_time'] }}"
                                        wire:click="selectSlot('{{ $slot['start_time'] }}')"
                                        @disabled(! $slot['available'])
                                        @class([
                                            'rounded-lg border px-2 py-2.5 text-sm font-semibold transition',
                                            'cursor-pointer border-court-700 bg-court-700 text-white' => $selected,
                                            'cursor-pointer border-zinc-300 bg-white hover:border-court-600 hover:bg-court-50' => $slot['available'] && ! $selected,
                                            'cursor-not-allowed border-transparent bg-zinc-100 text-zinc-400 line-through' => ! $slot['available'],
                                        ])>
                                        {{ \Carbon\Carbon::parse($slot['start_time'])->format('g:i A') }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-3 text-xs text-zinc-500">Tip: click the next free slot after your selection to add another hour.</p>
                        @endif
                    </div>
                </section>
            </div>

            {{-- Summary --}}
            <aside class="lg:sticky lg:top-6 lg:self-start">
                <div class="card overflow-hidden">
                    <div class="bg-court-800 px-5 py-4 text-white">
                        <h2 class="font-semibold">Your booking</h2>
                    </div>
                    <dl class="space-y-3 p-5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500">Court</dt>
                            <dd class="font-medium">{{ $court?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500">Date</dt>
                            <dd class="font-medium">{{ \Carbon\Carbon::parse($date)->format('D, M j, Y') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500">Time</dt>
                            <dd class="font-medium">
                                @if ($startTime)
                                    {{ \Carbon\Carbon::parse($startTime)->format('g:i A') }} – {{ \Carbon\Carbon::parse($endTime)->format('g:i A') }}
                                @else
                                    <span class="text-zinc-400">Pick a start time</span>
                                @endif
                            </dd>
                        </div>

                        @if ($startTime)
                            <div>
                                <dt class="mb-2 text-zinc-500">Duration</dt>
                                <dd class="flex flex-wrap gap-2">
                                    @for ($h = 1; $h <= $maxDuration; $h++)
                                        <button type="button" wire:click="setDuration({{ $h }})" wire:key="dur-{{ $h }}"
                                            @class([
                                                'cursor-pointer rounded-lg border px-3 py-1.5 text-sm font-semibold',
                                                'border-court-700 bg-court-700 text-white' => $h === $duration,
                                                'border-zinc-300 hover:border-court-600' => $h !== $duration,
                                            ])>
                                            {{ $h }} hr{{ $h > 1 ? 's' : '' }}
                                        </button>
                                    @endfor
                                </dd>
                            </div>
                        @endif

                        <div>
                            <label for="notes" class="mb-1.5 block text-zinc-500">Notes <span class="text-zinc-400">(optional)</span></label>
                            <textarea wire:model="notes" id="notes" rows="2" class="input" placeholder="e.g. Group of 4"></textarea>
                            @error('notes') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-baseline justify-between border-t border-zinc-200 pt-3">
                            <dt class="font-semibold">Court fee</dt>
                            <dd class="text-2xl font-bold">₱{{ number_format($total, 2) }}</dd>
                        </div>
                        @if ($startTime && $court)
                            <p class="-mt-2 text-right text-xs text-zinc-500">₱{{ number_format($court->hourly_rate, 2) }} × {{ $duration }} hr{{ $duration > 1 ? 's' : '' }}</p>
                        @endif
                    </dl>

                    <div class="px-5 pb-5">
                        @if ($errors->any())
                            <div class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <button type="button" wire:click="book" class="btn-accent w-full py-3 text-base" @disabled(! $startTime) wire:loading.attr="disabled" wire:target="book">
                            <span wire:loading.remove wire:target="book">{{ auth()->check() ? 'Book court' : 'Log in to book' }}</span>
                            <span wire:loading wire:target="book">Booking…</span>
                        </button>
                        <p class="mt-2 text-center text-xs text-zinc-500">Paddle rental is offered right after booking.</p>
                    </div>
                </div>
            </aside>
        </div>
    @endif
</div>
