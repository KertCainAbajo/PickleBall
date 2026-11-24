<div>
    <x-auth-shell title="Welcome back" subtitle="Log in to book courts and rent paddles." image="images/cta-player.jpg" position="55% 30%">
        <form wire:submit="login" class="space-y-5" novalidate>
            <div>
                <label for="email" class="label">Email</label>
                <input wire:model="email" id="email" type="email" class="input h-12 text-base" autocomplete="email" autofocus required
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <p id="email-error" class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <x-password-input id="password" model="password" autocomplete="current-password" required />
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 text-navy-700">
                <input wire:model="remember" type="checkbox" class="size-5 rounded-sm accent-navy-700">
                Keep me logged in on this device
            </label>

            <button type="submit" class="btn-accent h-12 w-full text-base" wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">Log in</span>
                <span wire:loading wire:target="login">Logging in</span>
            </button>
        </form>

        <x-slot:aside>
            New to the club?
            <a href="{{ route('register') }}" wire:navigate class="font-semibold text-navy-900 underline decoration-brand decoration-2 underline-offset-4 hover:decoration-navy-900">Create an account</a>
        </x-slot:aside>
    </x-auth-shell>
</div>
