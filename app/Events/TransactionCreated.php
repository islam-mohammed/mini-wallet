<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;


class TransactionCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $sender_balance,
        public string $receiver_balance,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("wallet.user.{$this->transaction->sender_id}"),
            new PrivateChannel("wallet.user.{$this->transaction->receiver_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'wallet.transaction.created';
    }

    public function broadcastWith(): array
    {
        return [
            'transaction'      => $this->transaction->toArray(),
            'sender_balance'   => $this->sender_balance,
            'receiver_balance' => $this->receiver_balance,
        ];
    }
}
