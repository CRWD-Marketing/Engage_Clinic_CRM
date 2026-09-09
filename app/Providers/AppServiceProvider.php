<?php

namespace App\Providers;

use App\Models\Service;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The "book a consultation" modal is duplicated across all 6 public
        // landing pages (no shared partial exists) - one composer keeps them
        // all reading the same active-services list with a single query,
        // instead of each route/controller fetching it separately.
        View::composer([
            'welcome',
            'landing_page.services',
            'landing_page.contact',
            'landing_page.blog',
            'landing_page.careers',
            'landing_page.about_us',
        ], function ($view) {
            $view->with('publicServices', Service::where('is_active', true)->orderBy('name')->get());
        });
    }
}
