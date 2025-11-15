<?php

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds demo users with correct balances', function () {
    $this->seed();

    $user1 = User::where('email', 'demo1@mini-wallet.test')->first();
    $user2 = User::where('email', 'demo2@mini-wallet.test')->first();

    expect($user1)->not->toBeNull();
    expect($user1->balance)->toBe('1000.0000');

    expect($user2)->not->toBeNull();
    expect($user2->balance)->toBe('500.0000');
});

it('seeds demo transactions for ui verification', function () {
    $this->seed();

    $user1 = User::where('email', 'demo1@mini-wallet.test')->first();
    $user2 = User::where('email', 'demo2@mini-wallet.test')->first();

    $transactions = Transaction::where(function ($q) use ($user1, $user2) {
        $q->where('sender_id', $user1->id)->where('receiver_id', $user2->id);
    })->orWhere(function ($q) use ($user1, $user2) {
        $q->where('sender_id', $user2->id)->where('receiver_id', $user1->id);
    })->get();

    expect($transactions)->toHaveCount(2);
});
