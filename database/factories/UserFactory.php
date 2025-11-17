<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Cache the hashed password across users created by the factory.
     */
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
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
        $amount = fake()->randomFloat(4, 100, 5000);

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
