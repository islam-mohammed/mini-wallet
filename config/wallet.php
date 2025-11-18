<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Idempotency TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | How long a transfer idempotency key should be considered valid.
    | This protects against accidental duplicate submissions within
    | a short window.
    |
    */

    'idempotency_ttl' => env('WALLET_IDEMPOTENCY_TTL', 300),
    'wallet.transfer_rate_limit' => env('WALLET_TRANSFER_RATE_LIMIT', 5),
];
