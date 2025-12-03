<?php

test('the application returns a successful response', function () {
    $response = $this->get('/logg-inn');

    $response->assertStatus(200);
});
