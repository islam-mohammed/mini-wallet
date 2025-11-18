<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('wallet-transfers', function ($request) {
            $userId = optional($request->user())->id ?? $request->ip();

            return Limit::perMinute(config('wallet.transfer_rate_limit', 5))->by($userId);
        });
    }
}
