<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{

    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(4, 1, 1000);
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
