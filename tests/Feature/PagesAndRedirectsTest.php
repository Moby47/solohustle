<?php

declare(strict_types=1);

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Redirects\Pages\CreateRedirect;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\EssentialsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function makePage(array $attributes = []): Page
{
    return Page::create([
        'title' => 'About us',
        'slug' => 'about-us',
        'body' => '<p>Hello there.</p>',
        'is_published' => true,
        ...$attributes,
    ]);
}

describe('public pages', function () {
    test('a published page is shown at its address', function () {
        makePage();

        $this->get('/about-us')->assertOk()->assertSee('About us')->assertSee('Hello there.');
    });

    test('draft pages are hidden from visitors but previewable by the admin', function () {
        makePage(['is_published' => false]);

        $this->get('/about-us')->assertNotFound();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/about-us')
            ->assertOk()
            ->assertSee('Draft preview')
            ->assertSee('noindex', false);
    });

    test('writers cannot preview drafts', function () {
        makePage(['is_published' => false]);

        $this->actingAs(User::factory()->create())->get('/about-us')->assertNotFound();
    });

    test('unsafe HTML in the body is removed', function () {
        makePage(['body' => '<p>Safe</p><script>alert(1)</script><img src="x" onerror="alert(2)">']);

        $this->get('/about-us')
            ->assertSee('Safe')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('onerror', false);
    });
});

describe('automatic redirects', function () {
    test('renaming a published page sends the old address to the new one', function () {
        $page = makePage();

        $page->update(['slug' => 'about']);

        $this->get('/about-us')->assertRedirect('/about')->assertStatus(301);
        expect(Redirect::first()->hits)->toBe(1);
    });

    test('renaming a draft does not create a redirect', function () {
        makePage(['is_published' => false])->update(['slug' => 'about']);

        expect(Redirect::count())->toBe(0);
    });

    test('renaming twice goes straight to the latest address, and renaming back removes the loop', function () {
        $page = makePage();

        $page->update(['slug' => 'about']);
        $page->update(['slug' => 'who-we-are']);

        expect(Redirect::where('from_path', '/about-us')->value('to_path'))->toBe('/who-we-are');

        $page->update(['slug' => 'about-us']);

        expect(Redirect::where('from_path', '/about-us')->exists())->toBeFalse();
        $this->get('/about-us')->assertOk();
    });
});

describe('manual redirects', function () {
    test('an old address redirects and keeps the query string', function () {
        Redirect::create(['from_path' => '/old-guide/', 'to_path' => '/new-guide']);

        $this->get('/old-guide?ref=newsletter')->assertRedirect('/new-guide?ref=newsletter')->assertStatus(301);
    });

    test('redirects never hide a page that exists', function () {
        makePage();
        Redirect::create(['from_path' => '/about-us', 'to_path' => '/elsewhere']);

        $this->get('/about-us')->assertOk();
    });

    test('the admin form rejects bad and circular redirects', function () {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateRedirect::class)
            ->fillForm(['from_path' => 'no-slash', 'to_path' => 'javascript:alert(1)'])
            ->call('create')
            ->assertHasFormErrors(['from_path', 'to_path']);

        Livewire::test(CreateRedirect::class)
            ->fillForm(['from_path' => '/same', 'to_path' => '/same/'])
            ->call('create')
            ->assertHasFormErrors(['to_path']);
    });
});

describe('pages in the dashboard', function () {
    beforeEach(function () {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    });

    test('the owner can add a page', function () {
        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Our team', 'slug' => 'our-team', 'body' => '<p>Hi</p>', 'is_published' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/our-team')->assertOk()->assertSee('Our team');
    });

    test('addresses used by the site itself are refused', function (string $slug) {
        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Clash', 'slug' => $slug])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    })->with(['jobs', 'admin', 'search', 'Not A Slug']);

    test('the edit form loads', function () {
        Livewire::test(EditPage::class, ['record' => makePage()->getRouteKey()])->assertOk();
    });
});

describe('essentials seeder', function () {
    test('creates the admin and the seven essential pages, and can run again safely', function () {
        $this->seed(EssentialsSeeder::class);
        $this->seed(EssentialsSeeder::class);

        expect(User::where('email', config('site.admin_email'))->first()->isAdmin())->toBeTrue()
            ->and(Page::published()->pluck('slug')->sort()->values()->all())->toBe([
                'about', 'advertise', 'contact', 'creator-guidelines', 'editorial-policy', 'privacy-policy', 'terms',
            ]);
    });

    test('never overwrites pages the owner has edited', function () {
        $this->seed(EssentialsSeeder::class);
        Page::where('slug', 'about')->update(['title' => 'My own title']);

        $this->seed(EssentialsSeeder::class);

        expect(Page::where('slug', 'about')->value('title'))->toBe('My own title');
    });
});
