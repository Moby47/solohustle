<?php

declare(strict_types=1);

test('the home page renders inside the site layout', function () {
    $this->withoutVite()
        ->get('/')
        ->assertOk()
        ->assertSee('Skip to content')
        ->assertSee('Solo Hustle')
        ->assertSee('href="'.route('home').'"', false);
});
