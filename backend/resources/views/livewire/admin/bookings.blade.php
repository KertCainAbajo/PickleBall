<div class="page">
    <x-admin-nav title="Bookings" subtitle="Confirm, complete or cancel customer bookings." />

    {{-- Filters --}}
    <div class="card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto]">
        <input wire:model.live.debounce.300ms="search" type="search" class="input" placeholder="Search reference, name or email" aria-label="Search">
        <select wire:model.live="status" class="input" aria-label="Status">
            <option value="">All statuses</option>
            @foreach (\App\Enums\BookingStatus::cases() as $case)
                <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
            @endforeach
        </select>
        <select wire:model.live="court" class="input" aria-label="Court">
            <option value="">All courts</option>
            @foreach ($courts as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </select>
        <input wire:model.live="date" type="date" class="input" aria-label="Date">
        <button type="button" wire:click="clearFilters" class="btn-secondary">Clear</button>
    </div>

    @if ($notice)
        <div class="mb-4 rounded-md border-l-4 border-ball-dark bg-white px-4 py-3 text-[0.9375rem] font-medium text-navy-900" role="status">{{ $notice }}</div>
    @endif
    @error('status')
        <div class="mb-4 rounded-md border-l-4 border-red-700 bg-red-50 px-4 py-3 text-[0.9375rem] font-medium text-red-800" role="alert">{{ $message }}</div>
    @enderror

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-rule bg-concrete/60 text-sm text-navy-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Booking</th>
                    <th class="px-4 py-3 font-semibold">Customer</th>
                    <th class="px-4 py-3 font-semibold">When</th>
                    <th class="px-4 py-3 font-semibold">Paddles</th>
                    <th class="px-4 py-3 text-right font-semibold">Total</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-rule">
                @forelse ($bookings as $booking)
                    <tr wire:key="admin-booking-{{ $booking->id }}" class="align-top">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $booking->reference }}</p>
                            <p class="text-xs text-navy-600">{{ $booking->court->name }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ $booking->user->name }}</p>
                            <p class="text-xs text-navy-600">{{ $booking->user->phone ?: $booking->user->email }}</p>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <p>{{ $booking->booking_date->format('M j, Y') }}</p>
                            <p class="text-xs text-navy-600">{{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-navy-600">
                            @forelse ($booking->rentals as $rental)
                                <p>{{ $rental->quantity }} {{ Str::plural($rental->paddle->name, $rental->quantity) }}</p>
                            @empty
                                <span class="text-navy-300">—</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3 text-right font-semibold whitespace-nowrap">₱{{ number_format($booking->total_amount, 2) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$booking->status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @if ($booking->status === \App\Enums\BookingStatus::Pending)
                                    <button type="button" wire:click="changeStatus({{ $booking->id }}, 'confirmed')" class="btn-primary btn-sm">Confirm</button>
                                @endif
                                @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
                                    <button type="button" wire:click="changeStatus({{ $booking->id }}, 'completed')" class="btn-primary btn-sm">Complete</button>
                                @endif
                                @if ($booking->status->isActive())
                                    <button type="button" wire:click="changeStatus({{ $booking->id }}, 'cancelled')"
                                            wire:confirm="Cancel booking {{ $booking->reference }}?" class="btn-secondary btn-sm text-red-600">Cancel</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-navy-600">No bookings match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $bookings->links() }}</div>
</div>
