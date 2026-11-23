<div class="page">
    <x-admin-nav title="Courts" subtitle="Set rates and opening hours, or hide a court from customers.">
        @unless ($showForm)
            <button type="button" wire:click="create" class="btn-accent">+ Add court</button>
        @endunless
    </x-admin-nav>

    @if ($notice)
        <div class="mb-4 rounded-md border-l-4 border-ball-dark bg-white px-4 py-3 text-[0.9375rem] font-medium text-navy-900" role="status">{{ $notice }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="card mb-6 p-6">
            <h2 class="mb-4 text-lg font-semibold">{{ $editingId ? 'Edit court' : 'New court' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="label">Name</label>
                    <input wire:model="name" id="name" type="text" class="input" placeholder="Court 5">
                    @error('name') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="surface_type" class="label">Type</label>
                    <input wire:model="surface_type" id="surface_type" type="text" class="input" placeholder="Indoor / Outdoor">
                    @error('surface_type') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="label">Description</label>
                    <textarea wire:model="description" id="description" rows="2" class="input"></textarea>
                    @error('description') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="hourly_rate" class="label">Hourly rate (₱)</label>
                    <input wire:model="hourly_rate" id="hourly_rate" type="number" step="0.01" min="0" class="input">
                    @error('hourly_rate') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="opening_time" class="label">Opens</label>
                        <input wire:model="opening_time" id="opening_time" type="time" step="3600" class="input">
                        @error('opening_time') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="closing_time" class="label">Closes</label>
                        <input wire:model="closing_time" id="closing_time" type="time" step="3600" class="input">
                        @error('closing_time') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-navy-900">
                    <input wire:model="is_active" type="checkbox" class="size-4 rounded accent-navy-700">
                    Open for booking
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" wire:click="resetForm" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">{{ $editingId ? 'Save changes' : 'Add court' }}</button>
            </div>
        </form>
    @endif

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-rule bg-concrete/60 text-sm text-navy-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Court</th>
                    <th class="px-4 py-3 font-semibold">Hours</th>
                    <th class="px-4 py-3 text-right font-semibold">Rate</th>
                    <th class="px-4 py-3 text-right font-semibold">Bookings</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-rule">
                @forelse ($courts as $court)
                    <tr wire:key="court-{{ $court->id }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $court->name }}</p>
                            <p class="text-xs text-navy-600">{{ $court->surface_type }}</p>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($court->opening_time)->format('g A') }} – {{ \Carbon\Carbon::parse($court->closing_time)->format('g A') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">₱{{ number_format($court->hourly_rate, 2) }}/hr</td>
                        <td class="px-4 py-3 text-right">{{ $court->bookings_count }}</td>
                        <td class="px-4 py-3">
                            <span @class(['badge', 'bg-navy-100 text-navy-800' => $court->is_active, 'bg-navy-100 text-navy-600' => ! $court->is_active])>
                                {{ $court->is_active ? 'Active' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="edit({{ $court->id }})" class="btn-secondary btn-sm">Edit</button>
                                <button type="button" wire:click="toggleActive({{ $court->id }})" class="btn-secondary btn-sm">{{ $court->is_active ? 'Deactivate' : 'Activate' }}</button>
                                @if ($court->bookings_count === 0)
                                    <button type="button" wire:click="delete({{ $court->id }})" wire:confirm="Delete {{ $court->name }}?" class="btn-secondary btn-sm text-red-600">Delete</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-navy-600">No courts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
