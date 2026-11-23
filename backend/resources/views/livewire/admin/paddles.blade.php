<div class="page">
    <x-admin-nav title="Paddles" subtitle="Rental paddles, prices and how many units you own.">
        @unless ($showForm)
            <button type="button" wire:click="create" class="btn-accent">+ Add paddle</button>
        @endunless
    </x-admin-nav>

    @if ($notice)
        <div class="mb-4 rounded-md border-l-4 border-ball-dark bg-white px-4 py-3 text-[0.9375rem] font-medium text-navy-900" role="status">{{ $notice }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="card mb-6 p-6">
            <h2 class="mb-4 text-lg font-semibold">{{ $editingId ? 'Edit paddle' : 'New paddle' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="label">Name</label>
                    <input wire:model="name" id="name" type="text" class="input" placeholder="Control Paddle">
                    @error('name') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="brand" class="label">Brand</label>
                    <input wire:model="brand" id="brand" type="text" class="input" placeholder="Selkirk">
                    @error('brand') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="label">Description</label>
                    <textarea wire:model="description" id="description" rows="2" class="input"></textarea>
                    @error('description') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="hourly_rate" class="label">Rental rate per hour (₱)</label>
                    <input wire:model="hourly_rate" id="hourly_rate" type="number" step="0.01" min="0" class="input">
                    @error('hourly_rate') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="stock_quantity" class="label">Units owned</label>
                    <input wire:model="stock_quantity" id="stock_quantity" type="number" min="0" class="input">
                    @error('stock_quantity') <p class="error">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-navy-900">
                    <input wire:model="is_active" type="checkbox" class="size-4 rounded accent-navy-700">
                    Available for rent
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" wire:click="resetForm" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">{{ $editingId ? 'Save changes' : 'Add paddle' }}</button>
            </div>
        </form>
    @endif

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-rule bg-concrete/60 text-sm text-navy-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Paddle</th>
                    <th class="px-4 py-3 text-right font-semibold">Rate</th>
                    <th class="px-4 py-3 text-right font-semibold">Units</th>
                    <th class="px-4 py-3 text-right font-semibold">Times rented</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-rule">
                @forelse ($paddles as $paddle)
                    <tr wire:key="paddle-{{ $paddle->id }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $paddle->name }}</p>
                            <p class="text-xs text-navy-600">{{ $paddle->brand }}</p>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">₱{{ number_format($paddle->hourly_rate, 2) }}/hr</td>
                        <td class="px-4 py-3 text-right">{{ $paddle->stock_quantity }}</td>
                        <td class="px-4 py-3 text-right">{{ $paddle->rentals_count }}</td>
                        <td class="px-4 py-3">
                            <span @class(['badge', 'bg-navy-100 text-navy-800' => $paddle->is_active, 'bg-navy-100 text-navy-600' => ! $paddle->is_active])>
                                {{ $paddle->is_active ? 'Active' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="edit({{ $paddle->id }})" class="btn-secondary btn-sm">Edit</button>
                                <button type="button" wire:click="toggleActive({{ $paddle->id }})" class="btn-secondary btn-sm">{{ $paddle->is_active ? 'Deactivate' : 'Activate' }}</button>
                                @if ($paddle->rentals_count === 0)
                                    <button type="button" wire:click="delete({{ $paddle->id }})" wire:confirm="Delete {{ $paddle->name }}?" class="btn-secondary btn-sm text-red-600">Delete</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-navy-600">No paddles yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
