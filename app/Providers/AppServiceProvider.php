<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\RateLimiter::for('whatsapp-messages', function ($job) {
            return (object) [
                'key' => 'whatsapp-messages',
                'maxAttempts' => 1,
                'decayMinutes' => 2 / 60,
            ];
        });
    }
}
