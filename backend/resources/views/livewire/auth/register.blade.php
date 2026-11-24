<div>
    <x-auth-shell title="Join the club" subtitle="One account to book courts and keep track of your games." image="images/step-play.jpg" position="40% 35%">
        <form wire:submit="register" class="space-y-5" novalidate>
            <div>
                <label for="name" class="label">Full name</label>
                <input wire:model="name" id="name" type="text" class="input h-12 text-base" autocomplete="name" autofocus required>
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="email" class="label">Email</label>
                    <input wire:model="email" id="email" type="email" class="input h-12 text-base" autocomplete="email" required>
                    @error('email') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="label">Mobile number <span class="font-normal text-navy-600">(optional)</span></label>
                    <input wire:model="phone" id="phone" type="tel" class="input h-12 text-base" autocomplete="tel" placeholder="09XX XXX XXXX">
                    @error('phone') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <x-password-input id="password" model="password" autocomplete="new-password" aria-describedby="password-hint" required />
                <p id="password-hint" class="mt-1.5 text-sm text-navy-600">At least 8 characters.</p>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="label">Type the password again</label>
                <x-password-input id="password_confirmation" model="password_confirmation" autocomplete="new-password" required />
            </div>

            <button type="submit" class="btn-accent h-12 w-full text-base" wire:loading.attr="disabled" wire:target="register">
                <span wire:loading.remove wire:target="register">Create account</span>
                <span wire:loading wire:target="register">Creating account</span>
            </button>
        </form>

        <x-slot:aside>
            Already a member?
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-navy-900 underline decoration-brand decoration-2 underline-offset-4 hover:decoration-navy-900">Log in</a>
        </x-slot:aside>
    </x-auth-shell>
</div>
