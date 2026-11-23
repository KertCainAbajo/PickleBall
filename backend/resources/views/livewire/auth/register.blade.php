<div class="page">
    <div class="mx-auto max-w-md overflow-hidden rounded-xl bg-white ring-1 ring-rule">
        <div class="court-surface px-8 pt-7 pb-6 text-line">
            <h1 class="font-display text-3xl">Join the club</h1>
            <p class="mt-2 text-line/70">An account lets you book courts and keep track of your games.</p>
        </div>

        <form wire:submit="register" class="space-y-5 px-8 py-7">
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
                <label for="phone" class="label">Mobile number <span class="font-normal text-navy-600">(optional, for booking updates)</span></label>
                <input wire:model="phone" id="phone" type="tel" class="input" autocomplete="tel" placeholder="09XX XXX XXXX">
                @error('phone') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <input wire:model="password" id="password" type="password" class="input" autocomplete="new-password" aria-describedby="password-hint" required>
                <p id="password-hint" class="mt-1.5 text-sm text-navy-600">At least 8 characters.</p>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="label">Type the password again</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" class="input" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn-accent w-full py-3 text-base" wire:loading.attr="disabled" wire:target="register">
                <span wire:loading.remove wire:target="register">Create account</span>
                <span wire:loading wire:target="register">Creating account</span>
            </button>

            <p class="text-center text-navy-700">
                Already a member?
                <a href="{{ route('login') }}" wire:navigate class="font-semibold text-navy-900 underline decoration-brand underline-offset-4">Log in</a>
            </p>
        </form>
    </div>
</div>
