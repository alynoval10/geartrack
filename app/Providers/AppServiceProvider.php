<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\User;
use App\Observers\AssetObserver;
use App\Services\BackupLock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BackupLock::class);
    }

    public function boot(): void
    {
        Gate::define('manage-backups', fn (User $user): bool => in_array(
            strtolower($user->email), config('backup.admin_emails', []), true,
        ));

        $publicUrl = config('app.public_url');

        if (filled($publicUrl)) {
            URL::useOrigin($publicUrl);
            URL::useAssetOrigin($publicUrl);
            URL::forceScheme(parse_url($publicUrl, PHP_URL_SCHEME));
        }

        Asset::observe(AssetObserver::class);
    }
}
