<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'PickleBall') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    <header class="bg-court-900 text-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6" x-data="{ open: false }">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5 text-lg font-bold tracking-tight">
                <img src="{{ asset('images/logo-sm.png') }}" alt="" class="h-11 w-auto" width="105" height="112">
                PickleBall
            </a>

            <button type="button" class="rounded-md p-2 hover:bg-white/10 sm:hidden" @click="open = !open" aria-label="Toggle menu">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            @php
                $link = fn (string $route) => request()->routeIs($route)
                    ? 'rounded-md bg-white/15 px-3 py-2 text-sm font-semibold'
                    : 'rounded-md px-3 py-2 text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white';
            @endphp

            <div class="absolute inset-x-0 top-14 z-20 flex-col gap-1 bg-court-900 px-4 pb-4 sm:static sm:flex sm:flex-row sm:items-center sm:gap-1 sm:p-0"
                 :class="open ? 'flex' : 'hidden'">
                <a href="{{ route('book') }}" wire:navigate class="{{ $link('book') }}">Book a Court</a>
                @auth
                    <a href="{{ route('bookings.index') }}" wire:navigate class="{{ $link('bookings.*') }}">My Bookings</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" wire:navigate class="{{ $link('admin.*') }}">Admin</a>
                    @endif
                    <div class="mt-2 flex items-center gap-3 border-t border-white/10 pt-3 sm:mt-0 sm:ml-3 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-4">
                        <span class="text-sm text-white/70">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md px-3 py-2 text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white">Log out</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="{{ $link('login') }}">Log in</a>
                    <a href="{{ route('register') }}" wire:navigate class="btn-accent btn-sm sm:ml-2">Sign up</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @if (session('success'))
            <div class="mb-6 rounded-xl border border-court-200 bg-court-50 px-4 py-3 text-sm font-medium text-court-800" role="status">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="border-t border-zinc-200 py-6 text-center text-xs text-zinc-500">
        &copy; {{ date('Y') }} PickleBall Court Booking
    </footer>
</body>
</html>
