<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create an account')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function register()
    {
        $validated = $this->validate();

        $user = User::create($validated); // role defaults to customer

        Auth::login($user);
        session()->regenerate();

        session()->flash('success', 'Welcome, '.$user->name.'! Your account is ready.');

        return $this->redirectIntended(route('book'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
