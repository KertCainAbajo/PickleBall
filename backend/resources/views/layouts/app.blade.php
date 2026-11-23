<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101c2c">
    <title>{{ isset($title) ? "{$title} | " : '' }}{{ config('app.name', 'PickleBall') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sm.png') }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-concrete font-sans text-navy-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

    <header class="relative z-30 border-b border-line/10 bg-navy-900 text-line">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-2.5 sm:px-6" x-data="{ open: false }" @keydown.escape.window="open = false">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3 rounded-sm">
                <img src="{{ asset('images/logo-sm.png') }}" alt="" class="h-11 w-auto" width="105" height="112">
                <span class="wordmark text-[1.375rem]">PickleBall</span>
                <span class="sr-only">Club home</span>
            </a>

            <button type="button" class="rounded-md p-2 text-line hover:bg-line/10 md:hidden" @click="open = !open" :aria-expanded="open" aria-controls="site-menu" aria-label="Menu">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>

            @php
                $link = fn (string $pattern) => request()->routeIs($pattern)
                    ? 'relative px-3 py-2 text-[0.9375rem] font-semibold text-line after:absolute after:inset-x-3 after:-bottom-[11px] after:h-[3px] after:bg-brand md:after:block after:hidden'
                    : 'px-3 py-2 text-[0.9375rem] font-medium text-line/75 hover:text-line';
            @endphp

            <div id="site-menu"
                 class="absolute inset-x-0 top-full flex-col gap-1 border-b border-line/10 bg-navy-900 px-4 pb-4 md:static md:flex md:flex-row md:items-center md:gap-1 md:border-0 md:p-0"
                 :class="open ? 'flex' : 'hidden'">
                <a href="{{ route('book') }}" wire:navigate class="{{ $link('book') }}">Book a court</a>
                @auth
                    <a href="{{ route('bookings.index') }}" wire:navigate class="{{ $link('bookings.*') }}">My bookings</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" wire:navigate class="{{ $link('admin.*') }}">Admin</a>
                    @endif
                    <div class="mt-2 flex items-center gap-2 border-t border-line/10 pt-3 md:mt-0 md:ml-3 md:border-t-0 md:border-l md:pt-0 md:pl-4">
                        <span class="px-1 text-sm text-line/60">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="cursor-pointer px-2 py-2 text-[0.9375rem] font-medium text-line/75 hover:text-line">Log out</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="{{ $link('login') }}">Log in</a>
                    <a href="{{ route('register') }}" wire:navigate class="btn-accent btn-sm mt-2 md:mt-0 md:ml-2">Sign up</a>
                @endauth
            </div>
        </nav>
    </header>

    <main id="main" class="flex-1">
        @if (session('success') || session('error'))
            <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6">
                @if (session('success'))
                    <p class="rounded-md border-l-4 border-ball-dark bg-white px-4 py-3 text-[0.9375rem] font-medium text-navy-900" role="status">{{ session('success') }}</p>
                @endif
                @if (session('error'))
                    <p class="rounded-md border-l-4 border-red-700 bg-white px-4 py-3 text-[0.9375rem] font-medium text-red-800" role="alert">{{ session('error') }}</p>
                @endif
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="bg-navy-900 text-line/60">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-6 text-sm sm:px-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-sm.png') }}" alt="" class="h-8 w-auto" width="105" height="112">
                <span>&copy; {{ date('Y') }} PickleBall Club</span>
            </div>
            <nav class="flex gap-5" aria-label="Footer">
                <a href="{{ route('book') }}" wire:navigate class="hover:text-line">Book a court</a>
                @auth
                    <a href="{{ route('bookings.index') }}" wire:navigate class="hover:text-line">My bookings</a>
                @endauth
            </nav>
        </div>
    </footer>
</body>
</html>
