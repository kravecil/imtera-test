<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Parsing\Browser\BrowserManager;
use App\Services\Parsing\Organization\OrganizationCache;
use App\Services\Parsing\Organization\OrganizationParser;
use App\Services\Parsing\Organization\OrganizationUrlBuilder;
use App\Services\Parsing\Reviews\ReviewsCache;
use App\Services\Parsing\Reviews\ReviewsParser;
use App\Services\Parsing\Reviews\ReviewsUrlBuilder;
use App\Services\Parsing\Utils\HtmlParser;
use App\Services\Parsing\Utils\ReviewHtmlExtractor;
use App\Services\Parsing\Utils\UrlParser;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BrowserManager::class);
        $this->app->singleton(HtmlParser::class);
        $this->app->singleton(ReviewHtmlExtractor::class);

        $this->app->singleton(OrganizationUrlBuilder::class);
        $this->app->singleton(OrganizationCache::class);
        $this->app->singleton(OrganizationParser::class);

        $this->app->singleton(ReviewsUrlBuilder::class);
        $this->app->singleton(ReviewsCache::class);
        $this->app->singleton(ReviewsParser::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
