<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $sender = User::where('email', 'alice@example.com')->first();
        $receiver = User::where('email', 'bob@example.com')->first();

        if (!$sender || !$receiver) {
            return;
        }

        Transaction::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => '50.0000',
            'commission_fee' => '0.7500',
        ]);

        Transaction::factory()->create([
            'sender_id' => $receiver->id,
            'receiver_id' => $sender->id,
            'amount' => '25.0000',
            'commission_fee' => '0.3750',
        ]);
    }
}
