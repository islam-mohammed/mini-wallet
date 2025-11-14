<?php

use function Pest\Laravel\get;

test('SPA entrypoint is reachable', function () {
    $response = get('/');

    $response->assertOk();
    $response->assertSee('Mini Wallet');
});
