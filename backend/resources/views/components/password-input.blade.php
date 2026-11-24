{{--
    Password field with a show/hide toggle.
    Props: id, model (Livewire property), autocomplete. Extra attributes go on the <input>.
--}}
@props(['id', 'model', 'autocomplete' => 'current-password'])

<div class="relative" x-data="{ show: false }">
    <input wire:model="{{ $model }}" id="{{ $id }}" :type="show ? 'text' : 'password'" type="password"
           autocomplete="{{ $autocomplete }}" {{ $attributes->merge(['class' => 'input h-12 pr-20 text-base']) }}>
    <button type="button" @click="show = !show"
            class="absolute inset-y-0 right-0 flex cursor-pointer items-center px-4 text-sm font-semibold text-navy-700 hover:text-navy-900"
            :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show.toString()">
        <span x-text="show ? 'Hide' : 'Show'">Show</span>
    </button>
</div>
