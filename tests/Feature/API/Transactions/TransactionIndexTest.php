<?php

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('requires authentication to list transactions', function () {
    $response = getJson('/api/transactions');

    $response->assertStatus(401);
});

it('returns authenticated user transactions and balance', function () {
    /** @var User $user */
    $user = User::factory()->create(['balance' => '500.0000']);
    /** @var User $other */
    $other = User::factory()->create();

    /** @var Transaction $t1 */
    $t1 = Transaction::factory()->create([
        'sender_id' => $user->id,
        'receiver_id' => $other->id,
        'amount' => '100.0000',
        'commission_fee' => '1.5000',
    ]);

    /** @var Transaction $t2 */
    $t2 = Transaction::factory()->create([
        'sender_id' => $other->id,
        'receiver_id' => $user->id,
        'amount' => '50.0000',
        'commission_fee' => '0.7500',
    ]);

    // Not related to $user
    Transaction::factory()->create([
        'sender_id' => $other->id,
        'receiver_id' => User::factory()->create()->id,
        'amount' => '10.0000',
        'commission_fee' => '0.1500',
    ]);

    Sanctum::actingAs($user);

    $response = getJson('/api/transactions');

    $response->assertOk();
    $response->assertJsonPath('meta.balance', '500.0000');

    $data = collect($response->json('data'));

    expect($data->count())->toBe(2);

    $ids = $data->pluck('id')->all();
    expect($ids)->toContain($t1->id);
    expect($ids)->toContain($t2->id);

    // ensure nested sender/receiver data exists (eager-loaded via resource)
    $data->each(function (array $tx) {
        expect($tx['sender'])->not->toBeNull();
        expect($tx['receiver'])->not->toBeNull();
    });
});
