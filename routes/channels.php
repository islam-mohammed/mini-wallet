<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'middleware' => ['auth:sanctum'],
]);

Broadcast::channel('wallet.user.{id}', function (User $user, int $id) {
    return (int) $user->id === (int) $id;
});
