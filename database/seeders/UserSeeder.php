<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Demo User 1',
            'email' => 'demo1@mini-wallet.test',
            'password' => Hash::make('password'),
            'balance' => '1000.0000',
        ]);

        User::factory()->create([
            'name' => 'Demo User 2',
            'email' => 'demo2@mini-wallet.test',
            'password' => Hash::make('password'),
            'balance' => '500.0000',
        ]);
    }
}
