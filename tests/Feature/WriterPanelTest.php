<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Writer\Pages\Auth\EditProfile;
use App\Filament\Writer\Pages\Auth\Login;
use App\Filament\Writer\Pages\Auth\Register;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('writer'));

    // The honeypot rejects forms submitted faster than a human could; tests submit instantly.
    config(['honeypot.amount_of_seconds' => 0]);
});

test('guests are sent to the writer login', function () {
    $this->get('/writer')->assertRedirect('/writer/login');
});

test('the login, register and password reset pages load', function (string $path) {
    $this->get($path)->assertOk();
})->with(['/writer/login', '/writer/register', '/writer/password-reset/request']);

test('anyone can register and becomes an unverified writer', function () {
    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Ada Writer',
            'email' => 'ada@example.com',
            'password' => 'a-long-password',
            'passwordConfirmation' => 'a-long-password',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'ada@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Writer)
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->slug)->toBe('ada-writer');
});

test('registration is blocked when the honeypot is filled', function () {
    Livewire::test(Register::class)
        ->set('extraFields.my_name', 'i am a bot')
        ->fillForm([
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'password' => 'a-long-password',
            'passwordConfirmation' => 'a-long-password',
        ])
        ->call('register')
        ->assertForbidden();

    expect(User::where('email', 'bot@example.com')->exists())->toBeFalse();
});

test('unverified writers are asked to verify their email', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/writer')
        ->assertRedirect('/writer/email-verification/prompt');
});

test('verified writers can open their dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/writer')
        ->assertOk();
});

test('admins cannot use the writer panel', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/writer')
        ->assertForbidden();
});

test('suspended writers are blocked with a clear message', function () {
    $writer = User::factory()->create(['email' => 'sus@example.com']);
    $writer->forceFill(['suspended_at' => now()])->save();

    Livewire::test(Login::class)
        ->fillForm(['email' => 'sus@example.com', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();

    $this->actingAs($writer)->get('/writer')->assertForbidden();
});

test('writers can update their public profile', function () {
    $writer = User::factory()->create();
    $this->actingAs($writer);

    Livewire::test(EditProfile::class)
        ->fillForm([
            'headline' => 'Freelance SEO writer',
            'bio' => 'I write about search.',
            'website_url' => 'https://ada.example.com',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($writer->fresh())
        ->headline->toBe('Freelance SEO writer')
        ->website_url->toBe('https://ada.example.com');
});

test('profile links must be http or https', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(EditProfile::class)
        ->fillForm(['website_url' => 'javascript:alert(1)'])
        ->call('save')
        ->assertHasFormErrors(['website_url']);
});

test('slugs stay unique', function () {
    $first = User::factory()->create(['name' => 'Sam Lee']);
    $second = User::factory()->create(['name' => 'Sam Lee']);

    expect($first->slug)->toBe('sam-lee')
        ->and($second->slug)->toBe('sam-lee-2');
});
