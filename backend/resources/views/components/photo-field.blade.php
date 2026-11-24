{{--
    Admin photo field for forms using App\Livewire\Concerns\ManagesPhoto.
    Props: photo, current (URL), remove (bool), fallback ('paddle' | 'court').
--}}
@props(['photo' => null, 'current' => null, 'remove' => false, 'fallback' => 'paddle'])

<div {{ $attributes }}>
    <span class="label">Photo</span>
    <div class="flex flex-wrap items-center gap-5">
        <div class="grid aspect-[4/3] w-40 place-items-center overflow-hidden rounded-lg bg-concrete ring-1 ring-rule">
            @if ($photo && ! $errors->has('photo'))
                <img src="{{ $photo->temporaryUrl() }}" alt="New photo preview" class="size-full object-cover">
            @elseif ($current && ! $remove)
                <img src="{{ $current }}" alt="Current photo" class="size-full object-cover">
            @elseif ($fallback === 'court')
                <x-court-diagram class="w-32 rounded-sm" />
            @else
                <x-paddle-art class="h-20 w-auto opacity-60" />
            @endif
        </div>
        <div class="space-y-2">
            <label class="btn-secondary btn-sm cursor-pointer">
                {{ ($photo || ($current && ! $remove)) ? 'Choose a different photo' : 'Upload a photo' }}
                <input wire:model="photo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
            <p wire:loading wire:target="photo" class="text-sm text-navy-600">Uploading</p>
            @if ($photo)
                <button type="button" wire:click="$set('photo', null)" class="block cursor-pointer text-sm font-semibold text-navy-700 hover:underline">Keep the old photo</button>
            @elseif ($current && ! $remove)
                <button type="button" wire:click="$set('removePhoto', true)" class="block cursor-pointer text-sm font-semibold text-red-700 hover:underline">Remove photo</button>
            @endif
            <p class="text-sm text-navy-600">JPG, PNG or WebP, up to 4 MB. A landscape (4:3) shot looks best.</p>
            @error('photo') <p class="error">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
