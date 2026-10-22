<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InitialAdminSetupController extends Controller
{
    private const ACTIVATION_LOCK_KEY = 'geartrack.initial_admin_activation_lock';

    public function create(): View|RedirectResponse
    {
        if ($this->administratorExists()) {
            return redirect()->route('filament.admin.auth.login');
        }

        return view('auth.initial-admin-setup');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->administratorExists()) {
            return redirect()->route('filament.admin.auth.login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $administrator = Cache::lock(self::ACTIVATION_LOCK_KEY, 20)->get(function () use ($data): ?User {
            if ($this->administratorExists()) {
                return null;
            }

            return DB::transaction(function () use ($data): ?User {
                if ($this->administratorExists()) {
                    return null;
                }

                return User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => 'admin',
                    'is_active' => true,
                    'must_change_password' => false,
                ]);
            });
        });

        if (! $administrator instanceof User) {
            if ($this->administratorExists()) {
                return redirect()->route('filament.admin.auth.login');
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['setup' => 'Persiapan Administrator sedang berlangsung. Silakan muat ulang halaman dan coba lagi.']);
        }

        Auth::guard('web')->login($administrator);
        $request->session()->regenerate();

        return redirect()->intended(route('filament.admin.pages.dashboard'));
    }

    private function administratorExists(): bool
    {
        return User::withTrashed()->where('role', 'admin')->exists();
    }
}
