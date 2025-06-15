<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Observers\BlogPostObserver;
use App\Observers\BlogCategoryObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        BlogPost::observe(BlogPostObserver::class);
        BlogCategory::observe(BlogCategoryObserver::class);
    }
}
