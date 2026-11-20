<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Page;
use App\Models\Post;
use App\Observers\SiteContentObserver;
use App\Support\MailSettings;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
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
        $this->app->make(MailSettings::class)->apply();

        // Contact and newsletter forms: plenty for a person, too few for a bot.
        RateLimiter::for('public-forms', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        foreach ([Post::class, Page::class, Category::class, Job::class, JobCategory::class] as $model) {
            $model::observe(SiteContentObserver::class);
        }
    }
}
