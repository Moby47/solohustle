<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are sent to the admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('the admin login page loads', function () {
    $this->get('/admin/login')->assertOk();
});

test('admins can open the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk();
});

test('writers cannot open the admin panel', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

test('new users are writers unless made admin on purpose', function () {
    $user = User::create(['name' => 'Sam', 'email' => 'sam@example.com', 'password' => 'secret-pass', 'role' => 'admin']);

    expect($user->fresh()->isAdmin())->toBeFalse();
});
