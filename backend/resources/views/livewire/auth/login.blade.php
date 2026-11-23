<div class="mx-auto max-w-md">
    <div class="card p-8">
        <h1 class="text-2xl font-bold tracking-tight">Welcome back</h1>
        <p class="mt-1 text-sm text-zinc-600">Log in to book a court and rent paddles.</p>

        <form wire:submit="login" class="mt-6 space-y-4">
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

            <label class="flex items-center gap-2 text-sm text-zinc-700">
                <input wire:model="remember" type="checkbox" class="size-4 rounded border-zinc-300 accent-court-700">
                Remember me
            </label>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Log in</span>
                <span wire:loading wire:target="login">Logging in…</span>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-600">
            No account yet?
            <a href="{{ route('register') }}" wire:navigate class="font-semibold text-court-700 hover:underline">Sign up</a>
        </p>
    </div>
</div>
