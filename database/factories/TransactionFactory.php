<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(4, 1, 1000);
        $commission = round($amount * 0.015, 4);

        return [
            'id' => Str::ulid()->toBase32(),
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'amount' => number_format($amount, 4, '.', ''),
            'commission_fee' => number_format($commission, 4, '.', ''),
        ];
    }
}
