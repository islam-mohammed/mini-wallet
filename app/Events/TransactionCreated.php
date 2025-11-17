<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $senderBalance,
        public string $receiverBalance,
    ) {
        $this->transaction->loadMissing(['sender', 'receiver']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('wallet.user.' . $this->transaction->sender_id),
            new PrivateChannel('wallet.user.' . $this->transaction->receiver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'wallet.transaction.created';
    }

    public function broadcastWith(): array
    {
        return [
            'transaction' => $this->transaction->toArray(),
            'sender_balance' => $this->senderBalance,
            'receiver_balance' => $this->receiverBalance,
        ];
    }
}
