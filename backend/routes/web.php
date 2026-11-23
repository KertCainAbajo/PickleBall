<?php

use App\Livewire\Admin;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\BookCourt;
use App\Livewire\Bookings;
use App\Livewire\Home;
use App\Livewire\RentPaddles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

// Anyone can browse courts and slots; booking itself asks for login.
Route::get('/book', BookCourt::class)->name('book');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');

    Route::get('/bookings', Bookings\Index::class)->name('bookings.index');
    Route::get('/bookings/{booking}', Bookings\Show::class)->name('bookings.show');
    Route::get('/bookings/{booking}/paddles', RentPaddles::class)->name('bookings.paddles');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\Dashboard::class)->name('dashboard');
    Route::get('/bookings', Admin\Bookings::class)->name('bookings');
    Route::get('/courts', Admin\Courts::class)->name('courts');
    Route::get('/paddles', Admin\Paddles::class)->name('paddles');
});
