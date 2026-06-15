<?php

it('redirects the home page to the admin panel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/admin');
});
