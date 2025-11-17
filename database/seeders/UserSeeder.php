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
            'name' => 'Alice',
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
            'balance' => '1000.0000',
        ]);

        User::factory()->create([
            'name' => 'Bob',
            'username' => 'bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
            'balance' => '500.0000',
        ]);
    }
}
