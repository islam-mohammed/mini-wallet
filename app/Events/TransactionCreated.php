<?php

namespace App\Events;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $senderBalance,
        public string $receiverBalance,
    ) {
        // Ensure relations are loaded for the broadcast payload
        $this->transaction->loadMissing(['sender', 'receiver']);
    }

    /**
     * Broadcast on sender & receiver private channels.
     *
     * Channels: private-user.{id}
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->transaction->sender_id}"),
            new PrivateChannel("user.{$this->transaction->receiver_id}"),
        ];
    }

    /**
     * Custom event name for frontend.
     */
    public function broadcastAs(): string
    {
        return 'transaction.created';
    }

    /**
     * Payload sent to the frontend.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'transaction' => [
                'id' => $this->transaction->id,
                'sender_id' => $this->transaction->sender_id,
                'receiver_id' => $this->transaction->receiver_id,
                'amount' => (string) $this->transaction->amount,
                'commission_fee' => (string) $this->transaction->commission_fee,
                'created_at' => $this->transaction->created_at?->toISOString(),
                'sender' => [
                    'id' => $this->transaction->sender->id,
                    'name' => $this->transaction->sender->name,
                ],
                'receiver' => [
                    'id' => $this->transaction->receiver->id,
                    'name' => $this->transaction->receiver->name,
                ],
            ],
            'sender_balance' => $this->senderBalance,
            'receiver_balance' => $this->receiverBalance,
        ];
    }
}
