<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Transaction */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        $direction = null;
        if ($userId !== null) {
            if ($this->sender_id === $userId) {
                $direction = 'outgoing';
            } elseif ($this->receiver_id === $userId) {
                $direction = 'incoming';
            }
        }

        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'amount' => (string) $this->amount,
            'commission_fee' => (string) $this->commission_fee,
            'direction' => $direction,
            'created_at' => $this->created_at?->toISOString(),

            // Minimal nested data to avoid N+1 in the frontend
            'sender' => $this->whenLoaded('sender', function () {
                return [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                ];
            }),
            'receiver' => $this->whenLoaded('receiver', function () {
                return [
                    'id' => $this->receiver->id,
                    'name' => $this->receiver->name,
                ];
            }),
        ];
    }
}
