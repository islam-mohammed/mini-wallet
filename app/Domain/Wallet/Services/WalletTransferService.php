<?php

namespace App\Domain\Wallet\Services;

use App\Domain\Wallet\Contracts\TransfersMoney;
use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Exceptions\InvalidTransferException;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletTransferService implements TransfersMoney
{
    public function transfer(User $sender, User $receiver, string $amount): Transaction
    {
        // Basic guard clauses
        if ($sender->is($receiver)) {
            throw new InvalidTransferException('Sender and receiver must be different users.');
        }

        $normalizedAmount = $this->normalizeAmount($amount);

        if ($this->isNonPositive($normalizedAmount)) {
            throw new InvalidTransferException('Transfer amount must be greater than zero.');
        }

        $commission = $this->calculateCommission($normalizedAmount);
        $totalDebit = $this->add($normalizedAmount, $commission);

        return DB::transaction(function () use ($sender, $receiver, $normalizedAmount, $commission, $totalDebit) {
            // Lock both users in a consistent order to avoid deadlocks
            $senderId = $sender->getKey();
            $receiverId = $receiver->getKey();

            $ids = [$senderId, $receiverId];
            sort($ids);

            /** @var \Illuminate\Support\Collection<int,User> $lockedUsers */
            $lockedUsers = User::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            /** @var User $lockedSender */
            $lockedSender = $lockedUsers[$senderId];
            /** @var User $lockedReceiver */
            $lockedReceiver = $lockedUsers[$receiverId];

            // Ensure sender has enough balance
            if ($this->isLessThan($lockedSender->balance, $totalDebit)) {
                throw new InsufficientBalanceException();
            }

            // Update balances
            $lockedSender->balance = $this->subtract($lockedSender->balance, $totalDebit);
            $lockedReceiver->balance = $this->add($lockedReceiver->balance, $normalizedAmount);

            $lockedSender->save();
            $lockedReceiver->save();

            // Create transaction record
            /** @var Transaction $transaction */
            $transaction = Transaction::query()->create([
                'id' => Str::ulid()->toBase32(),
                'sender_id' => $lockedSender->getKey(),
                'receiver_id' => $lockedReceiver->getKey(),
                'amount' => $normalizedAmount,
                'commission_fee' => $commission,
            ]);

            return $transaction;
        });
    }

    /**
     * Normalize a decimal string to scale 4.
     */
    private function normalizeAmount(string $amount): string
    {
        // Requires ext-bcmath, common in PHP setups.
        return bcadd($amount, '0', 4);
    }

    private function calculateCommission(string $amount): string
    {
        // 1.5% commission
        return bcmul($amount, '0.015', 4);
    }

    private function add(string $left, string $right): string
    {
        return bcadd($left, $right, 4);
    }

    private function subtract(string $left, string $right): string
    {
        return bcsub($left, $right, 4);
    }

    private function isNonPositive(string $amount): bool
    {
        return bccomp($amount, '0', 4) <= 0;
    }

    private function isLessThan(string $left, string $right): bool
    {
        return bccomp($left, $right, 4) === -1;
    }
}
