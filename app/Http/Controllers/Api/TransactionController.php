<?php

namespace App\Http\Controllers\Api;

use App\Domain\Wallet\Contracts\TransfersMoney;
use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Exceptions\InvalidTransferException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionStoreRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Events\TransactionCreated;
use Illuminate\Support\Facades\Cache;

class TransactionController extends Controller
{
    /**
     * GET /api/transactions
     *
     * Returns transaction history (incoming + outgoing) + current balance.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $transactions = Transaction::query()
            ->with(['sender', 'receiver']) // avoid N+1
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->cursorPaginate(20);

        // Refresh balance from DB to avoid stale values
        $user->refresh();

        return response()->json([
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'balance' => (string) $user->balance,
                'next_cursor' => $transactions->nextCursor()?->encode(),
            ],
        ]);
    }

    /**
     * POST /api/transactions
     *
     * Executes a new money transfer.
     */
    public function store(
        TransactionStoreRequest $request,
        TransfersMoney $transfersMoney
    ): JsonResponse {
        /** @var User $sender */
        $sender = $request->user();

        $idempotencyKey = $request->header('Idempotency-Key');
        $lockCacheKey = null;

        if ($idempotencyKey) {
            $lockCacheKey = 'wallet:idempotency:' . $idempotencyKey;

            $ttlSeconds = (int) config('wallet.idempotency_ttl', 300);

            // Cache::add is atomic - succeeds only if key doesn't exist.
            $acquired = Cache::add($lockCacheKey, 'locked', $ttlSeconds);

            if (! $acquired) {
                return response()->json([
                    'message' => 'Duplicate transfer request.',
                ], 409);
            }
        }

        /** @var User $receiver */
        $receiver = User::where('username', $request->validated('receiver_username'))
            ->firstOrFail();

        $amount = (string) $request->input('amount');

        try {
            $transaction = $transfersMoney->transfer(
                sender: $sender,
                receiver: $receiver,
                amount: $amount,
            );

            $sender->refresh();
            $receiver->refresh();

            event(new TransactionCreated(
                $transaction->fresh(['sender', 'receiver']),
                (string) $sender->balance,
                (string) $receiver->balance,
            ));

            return response()->json([
                'data' => new TransactionResource(
                    $transaction->load(['sender', 'receiver'])
                ),
                'meta' => [
                    'balance' => (string) $sender->balance,
                ],
            ], 201);
        } catch (InsufficientBalanceException $exception) {
            if ($lockCacheKey) {
                Cache::forget($lockCacheKey);
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'amount' => [$exception->getMessage()],
                ],
            ], 422);
        } catch (InvalidTransferException $exception) {
            if ($lockCacheKey) {
                Cache::forget($lockCacheKey);
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'amount' => [$exception->getMessage()],
                ],
            ], 422);
        }
    }
}
