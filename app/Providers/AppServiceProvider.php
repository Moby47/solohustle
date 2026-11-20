<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Observers\SiteContentObserver;
use App\Support\MailSettings;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Catch slow "one query per row" code while developing and testing; never break the live site over it.
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->app->make(MailSettings::class)->apply();

        // Contact and newsletter forms: plenty for a person, too few for a bot.
        RateLimiter::for('public-forms', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Users too: names, photos and bios appear in bylines and author pages.
        foreach ([Post::class, Page::class, Category::class, Job::class, JobCategory::class, User::class] as $model) {
            $model::observe(SiteContentObserver::class);
        }
    }
}
