<?php

declare(strict_types=1);

test('the application returns a successful response', function () {
    $response = $this->get('/logg-inn');

    $response->assertStatus(200);
});
