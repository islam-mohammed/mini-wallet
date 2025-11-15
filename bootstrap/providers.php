<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,

    // Sanctum
    Laravel\Sanctum\SanctumServiceProvider::class,

    // Wallet domain
    App\Providers\WalletServiceProvider::class,
];
