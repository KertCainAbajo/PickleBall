<div class="mx-auto max-w-md">
    <div class="card p-8">
        <h1 class="text-2xl font-bold tracking-tight">Create your account</h1>
        <p class="mt-1 text-sm text-zinc-600">It takes less than a minute.</p>

        <form wire:submit="register" class="mt-6 space-y-4">
            <div>
                <label for="name" class="label">Full name</label>
                <input wire:model="name" id="name" type="text" class="input" autocomplete="name" autofocus required>
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="label">Email</label>
                <input wire:model="email" id="email" type="email" class="input" autocomplete="email" required>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="label">Mobile number <span class="font-normal text-zinc-400">(optional)</span></label>
                <input wire:model="phone" id="phone" type="tel" class="input" autocomplete="tel" placeholder="09XX XXX XXXX">
                @error('phone') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <input wire:model="password" id="password" type="password" class="input" autocomplete="new-password" required>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="label">Confirm password</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" class="input" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="register">Create account</span>
                <span wire:loading wire:target="register">Creating…</span>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-600">
            Already have an account?
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-court-700 hover:underline">Log in</a>
        </p>
    </div>
</div>
