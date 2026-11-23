<div class="page">
    <div class="mx-auto max-w-md overflow-hidden rounded-xl bg-white ring-1 ring-rule">
        <div class="court-surface px-8 pt-7 pb-6 text-line">
            <h1 class="font-display text-3xl">Log in</h1>
            <p class="mt-2 text-line/70">Book courts, rent paddles and see your bookings.</p>
        </div>

        <form wire:submit="login" class="space-y-5 px-8 py-7">
            <div>
                <label for="email" class="label">Email</label>
                <input wire:model="email" id="email" type="email" class="input" autocomplete="email" autofocus required>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <input wire:model="password" id="password" type="password" class="input" autocomplete="current-password" required>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-navy-700">
                <input wire:model="remember" type="checkbox" class="size-4 rounded-sm accent-navy-700">
                Keep me logged in
            </label>

            <button type="submit" class="btn-accent w-full py-3 text-base" wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">Log in</span>
                <span wire:loading wire:target="login">Logging in</span>
            </button>

            <p class="text-center text-navy-700">
                New to the club?
                <a href="{{ route('register') }}" wire:navigate class="font-semibold text-navy-900 underline decoration-brand underline-offset-4">Create an account</a>
            </p>
        </form>
    </div>
</div>
