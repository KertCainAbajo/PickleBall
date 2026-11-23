@props(['title', 'subtitle' => null])

<div class="mb-6">
    <nav class="mb-4 flex flex-wrap gap-1 border-b border-rule" aria-label="Admin">
        @foreach ([
            'admin.dashboard' => 'Dashboard',
            'admin.bookings' => 'Bookings',
            'admin.courts' => 'Courts',
            'admin.paddles' => 'Paddles',
        ] as $route => $label)
            <a href="{{ route($route) }}" wire:navigate
               @class([
                   '-mb-px border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                   'border-brand text-navy-900' => request()->routeIs($route),
                   'border-transparent text-navy-600 hover:text-navy-900' => ! request()->routeIs($route),
               ])>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title text-4xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-navy-600">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $slot }}
    </div>
</div>
