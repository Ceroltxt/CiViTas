<?php

test('health endpoint responds successfully', function () {
    $response = $this->get('/up');

    $response->assertOk();
});

test('api status returns application metadata', function () {
    $response = $this->getJson(route('api.status'));

    $response
        ->assertOk()
        ->assertJsonStructure([
            'name',
            'environment',
            'status',
            'database' => ['connection', 'connected'],
        ])
        ->assertJson([
            'status' => 'ok',
            'database' => [
                'connection' => 'sqlite',
                'connected' => true,
            ],
        ]);
});
