<?php

test('login page is available', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee(config('app.name'));
});
