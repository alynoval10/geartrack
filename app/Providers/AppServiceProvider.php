<?php

namespace App\Providers;

use App\Models\Asset;
use App\Observers\AssetObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $publicUrl = config('app.public_url');

        if (filled($publicUrl)) {
            URL::useOrigin($publicUrl);
            URL::useAssetOrigin($publicUrl);
            URL::forceScheme(parse_url($publicUrl, PHP_URL_SCHEME));
        }

        Asset::observe(AssetObserver::class);
    }
}
