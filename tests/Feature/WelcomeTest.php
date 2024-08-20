<?php

it('shows LSS', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('LSS');
});
