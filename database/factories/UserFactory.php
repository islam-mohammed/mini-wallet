<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;


    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Wallet-specific field
            'balance' => '0.0000',
        ];
    }

    /**
     * Give the user a random positive balance (useful for transfer tests).
     */
    public function rich(): static
    {
        $amount = $this->faker->randomFloat(4, 100, 5000);

        return $this->state(fn() => [
            'balance' => number_format($amount, 4, '.', ''),
        ]);
    }

    /**
     * Force the user balance to exactly zero.
     */
    public function zeroBalance(): static
    {
        return $this->state(fn() => [
            'balance' => '0.0000',
        ]);
    }
}
