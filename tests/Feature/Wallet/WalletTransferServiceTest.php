<?php

use App\Domain\Wallet\Contracts\TransfersMoney;
use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Exceptions\InvalidTransferException;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('transfers money with commission and updates balances atomically', function () {
    /** @var User $sender */
    $sender = User::factory()->create([
        'balance' => '1000.0000',
    ]);

    /** @var User $receiver */
    $receiver = User::factory()->create([
        'balance' => '0.0000',
    ]);

    /** @var TransfersMoney $service */
    $service = app(TransfersMoney::class);

    $transaction = $service->transfer($sender, $receiver, '100.0000');

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect($transaction->sender_id)->toBe($sender->id);
    expect($transaction->receiver_id)->toBe($receiver->id);
    expect($transaction->amount)->toBe('100.0000');
    expect($transaction->commission_fee)->toBe('1.5000');

    $sender->refresh();
    $receiver->refresh();

    // 1000 - (100 + 1.5) = 898.5
    expect($sender->balance)->toBe('898.5000');
    expect($receiver->balance)->toBe('100.0000');
});

it('throws when sender balance is insufficient and leaves balances unchanged', function () {
    /** @var User $sender */
    $sender = User::factory()->create([
        'balance' => '50.0000',
    ]);

    /** @var User $receiver */
    $receiver = User::factory()->create([
        'balance' => '0.0000',
    ]);

    /** @var TransfersMoney $service */
    $service = app(TransfersMoney::class);

    expect(fn () => $service->transfer($sender, $receiver, '100.0000'))
        ->toThrow(InsufficientBalanceException::class);

    $sender->refresh();
    $receiver->refresh();

    expect($sender->balance)->toBe('50.0000');
    expect($receiver->balance)->toBe('0.0000');
    expect(Transaction::query()->count())->toBe(0);
});

it('rejects invalid transfers (non-positive amount or same sender and receiver)', function () {
    /** @var User $sender */
    $sender = User::factory()->create([
        'balance' => '100.0000',
    ]);

    /** @var User $receiver */
    $receiver = User::factory()->create([
        'balance' => '0.0000',
    ]);

    /** @var TransfersMoney $service */
    $service = app(TransfersMoney::class);

    // Non-positive amount
    expect(fn () => $service->transfer($sender, $receiver, '0.0000'))
        ->toThrow(InvalidTransferException::class);

    expect(fn () => $service->transfer($sender, $receiver, '-10.0000'))
        ->toThrow(InvalidTransferException::class);

    // Same sender & receiver
    expect(fn () => $service->transfer($sender, $sender, '10.0000'))
        ->toThrow(InvalidTransferException::class);

    expect(Transaction::query()->count())->toBe(0);
});

