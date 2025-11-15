<?php

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Events\TransactionCreated;
use Illuminate\Support\Facades\Event;


use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('requires authentication to create a transaction', function () {
    $response = postJson('/api/transactions', [
        'receiver_id' => 1,
        'amount' => '100.00',
    ]);

    $response->assertStatus(401);
});

it('creates a transaction and returns updated balance', function () {
    /** @var User $sender */
    $sender = User::factory()->create(['balance' => '1000.0000']);
    /** @var User $receiver */
    $receiver = User::factory()->create(['balance' => '0.0000']);

    Sanctum::actingAs($sender);

    Event::fake([TransactionCreated::class]);

    $response = postJson('/api/transactions', [
        'receiver_id' => $receiver->id,
        'amount' => '100.0000',
    ]);

    $response->assertCreated();

    $response->assertJsonPath('data.sender_id', $sender->id);
    $response->assertJsonPath('data.receiver_id', $receiver->id);
    $response->assertJsonPath('data.amount', '100.0000');
    $response->assertJsonPath('data.commission_fee', '1.5000');

    $sender->refresh();
    $receiver->refresh();

    expect($sender->balance)->toBe('898.5000');
    expect($receiver->balance)->toBe('100.0000');

    $response->assertJsonPath('meta.balance', '898.5000');

    expect(Transaction::query()->count())->toBe(1);

    Event::assertDispatched(TransactionCreated::class, function (TransactionCreated $event) use ($sender, $receiver) {
        return
            $event->transaction->sender_id === $sender->id &&
            $event->transaction->receiver_id === $receiver->id &&
            $event->senderBalance === '898.5000' &&
            $event->receiverBalance === '100.0000';
    });
});


it('returns validation errors for invalid payload', function () {
    /** @var User $user */
    $user = User::factory()->create(['balance' => '1000.0000']);

    Sanctum::actingAs($user);

    $response = postJson('/api/transactions', []);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['receiver_id', 'amount']);
});

it('returns error when balance is insufficient', function () {
    /** @var User $sender */
    $sender = User::factory()->create(['balance' => '10.0000']);
    /** @var User $receiver */
    $receiver = User::factory()->create(['balance' => '0.0000']);

    Sanctum::actingAs($sender);

    $response = postJson('/api/transactions', [
        'receiver_id' => $receiver->id,
        'amount' => '100.0000',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'Insufficient balance for this transfer.');

    $sender->refresh();
    $receiver->refresh();

    expect($sender->balance)->toBe('10.0000');
    expect($receiver->balance)->toBe('0.0000');
    expect(Transaction::query()->count())->toBe(0);
});
