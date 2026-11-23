<?php

namespace App\Livewire;

use App\Models\Court;
use App\Models\Paddle;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Book a Pickleball Court')]
class Home extends Component
{
    public function render()
    {
        return view('livewire.home', [
            'courts' => Court::active()->orderBy('name')->get(),
            'paddles' => Paddle::active()->orderBy('hourly_rate')->get(),
        ]);
    }
}
