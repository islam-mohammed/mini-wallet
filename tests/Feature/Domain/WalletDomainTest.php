<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists user balance as a high-precision decimal', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'balance' => '123.4500',
    ]);

    $user->refresh();

    expect($user->balance)
        ->toBe('123.4500');
});

it('persists transactions with sender and receiver relations', function () {
    /** @var User $sender */
    $sender = User::factory()->create(['balance' => '1000.0000']);

    /** @var User $receiver */
    $receiver = User::factory()->create(['balance' => '0.0000']);

    /** @var Transaction $transaction */
    $transaction = Transaction::factory()->create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => '100.0000',
        'commission_fee' => '1.5000',
    ]);

    expect($transaction->sender->is($sender))->toBeTrue();
    expect($transaction->receiver->is($receiver))->toBeTrue();

    // Relationship collections
    expect($sender->sentTransactions)
        ->toHaveCount(1)
        ->first()->id->toBe($transaction->id);

    expect($receiver->receivedTransactions)
        ->toHaveCount(1)
        ->first()->id->toBe($transaction->id);
});

it('supports eager loading of transaction relations to avoid n_plus_one', function () {
    /** @var User $sender */
    $sender = User::factory()->create();
    /** @var User $receiver */
    $receiver = User::factory()->create();

    Transaction::factory()
        ->count(3)
        ->for($sender, 'sender')
        ->for($receiver, 'receiver')
        ->create();

    // This is mostly a smoke test / contract: we should be able to eager-load
    $transactions = Transaction::query()
        ->with(['sender', 'receiver'])
        ->get();

    expect($transactions)->toHaveCount(3);
    $transactions->each(function (Transaction $transaction) use ($sender, $receiver) {
        expect($transaction->sender->is($sender))->toBeTrue();
        expect($transaction->receiver->is($receiver))->toBeTrue();
    });
});
