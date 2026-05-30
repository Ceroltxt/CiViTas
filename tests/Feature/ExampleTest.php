<?php

test('health endpoint responds successfully', function () {
    $response = $this->get('/up');

    $response->assertOk();
});

test('api status returns application metadata', function () {
    $response = $this->getJson(route('status'));

    $response
        ->assertOk()
        ->assertJsonStructure(['name', 'environment', 'status'])
        ->assertJson([
            'status' => 'ok',
        ]);
});
