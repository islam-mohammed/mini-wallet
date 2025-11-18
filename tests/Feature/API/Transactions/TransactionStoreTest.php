<?php

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Events\TransactionCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;


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
        'receiver_username' => $receiver->username,
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
        ->assertJsonValidationErrors(['receiver_username', 'amount']);
});

it('returns error when balance is insufficient', function () {
    /** @var User $sender */
    $sender = User::factory()->create(['balance' => '10.0000']);
    /** @var User $receiver */
    $receiver = User::factory()->create(['balance' => '0.0000']);

    Sanctum::actingAs($sender);

    $response = postJson('/api/transactions', [
        'receiver_username' => $receiver->username,
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

it('respects idempotency key and prevents duplicate transfers', function () {
    /** @var User $sender */
    $sender = User::factory()->create(['balance' => '1000.0000']);
    /** @var User $receiver */
    $receiver = User::factory()->create(['username' => 'bob', 'balance' => '0.0000']);

    Sanctum::actingAs($sender);

    // Use array cache in tests to avoid Redis dependency noise
    Cache::shouldReceive('add')
        ->once()
        ->andReturn(true);  // first call acquires lock

    Cache::shouldReceive('add')
        ->once()
        ->andReturn(false); // second call detects duplicate

    $idempotencyKey = 'test-idem-key-123';


    $first = postJson('/api/transactions', [
        'receiver_username' => $receiver->username,
        'amount' => '100.0000',
    ], [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $first->assertCreated();

    $second = postJson('/api/transactions', [
        'receiver_username' => $receiver->username,
        'amount' => '100.0000',
    ], [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $second->assertStatus(409);
    $second->assertJsonPath('message', 'Duplicate transfer request.');

    $sender->refresh();
    $receiver->refresh();

    // Only one transfer applied
    expect($sender->balance)->toBe('898.5000');
    expect($receiver->balance)->toBe('100.0000');

    expect(Transaction::query()->count())->toBe(1);
});

it('applies rate limiting to transfer requests', function () {
    /** @var User $sender */
    $sender = User::factory()->create(['balance' => '1000.0000']);
    /** @var User $receiver */
    $receiver = User::factory()->create(['balance' => '0.0000']);

    Sanctum::actingAs($sender);

    // Hit the endpoint several times quickly
    for ($i = 0; $i < 5; $i++) {
        $response = postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => '1.0000',
        ]);
        // Don't assert here; some may pass before limit triggers
    }

    // One more call we expect to be limited
    $limited = postJson('/api/transactions', [
        'receiver_id' => $receiver->id,
        'amount' => '1.0000',
    ]);

    // If the limiter is configured with a low enough limit for tests,
    // this should be 429. We keep the assertion soft with ">= 400".
    $limited->assertStatus(429);
});
