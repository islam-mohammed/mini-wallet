<?php

namespace App\Providers;

use App\Domain\Wallet\Contracts\TransfersMoney;
use App\Domain\Wallet\Services\WalletTransferService;
use Illuminate\Support\ServiceProvider;

class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransfersMoney::class, WalletTransferService::class);
    }

    public function boot(): void
    {
        //
    }
}
