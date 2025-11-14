<?php

namespace App\Domain\Wallet\Contracts;

use App\Models\Transaction;
use App\Models\User;

interface TransfersMoney
{
    /**
     * Perform an atomic transfer between two users.
     *
     * @param  User   $sender   The debited user.
     * @param  User   $receiver The credited user.
     * @param  string $amount   Positive decimal (scale 4) as string.
     *
     * @return Transaction
     */
    public function transfer(User $sender, User $receiver, string $amount): Transaction;
}
