<?php

namespace App\Providers;

use App\Models\Asset;
use App\Observers\AssetObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Asset::observe(AssetObserver::class);
    }
}