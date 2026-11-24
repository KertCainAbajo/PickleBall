<?php

namespace App\Providers;

use App\Console\PrepareLocalServer;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // On a local machine, `php artisan serve` first migrates the database and rebuilds styles if needed.
        if ($this->app->runningInConsole()
            && $this->app->environment('local')
            && config('app.auto_update_on_serve')) {
            Event::listen(CommandStarting::class, PrepareLocalServer::class);
        }
    }
}
