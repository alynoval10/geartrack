<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;

class Login extends BaseLogin
{
    public function mount(): void
    {
        if (! User::withTrashed()->where('role', 'admin')->exists()) {
            $this->redirectRoute('initial-admin.setup');

            return;
        }

        parent::mount();
    }

    /**
     * Authenticate the user and discard a stale forced-password destination.
     */
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if ($response && ! Filament::auth()->user()?->must_change_password) {
            $intendedUrl = session()->get('url.intended');
            $passwordChangePath = parse_url(route('filament.admin.pages.change-password'), PHP_URL_PATH);
            $intendedPath = is_string($intendedUrl) ? parse_url($intendedUrl, PHP_URL_PATH) : null;

            if ($intendedPath === $passwordChangePath) {
                session()->forget('url.intended');
            }
        }

        return $response;
    }

    public function getHeading(): string
    {
        return 'Masuk ke GearTrack';
    }

    public function getSubheading(): ?string
    {
        if (! User::withTrashed()->where('role', 'admin')->exists()) {
            return 'Administrator utama belum dibuat. Selesaikan pengaturan awal sebelum masuk.';
        }

        if (session()->has('session_status')) {
            return session('session_status');
        }

        if (session()->has('backup_status')) {
            return session('backup_status');
        }

        return 'Kelola inventaris perangkat TKJ dengan lebih cepat dan terorganisir.';
    }
}
