<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnforceIdleSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Mengganti password sendiri, mencabut sesi lain, dan mempertahankan sesi saat ini.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
            'session_version' => $user->session_version + 1,
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        // Versi baru disimpan agar sesi yang sedang mengganti password tetap aktif.
        $request->session()->put(EnforceIdleSession::USER_VERSION_KEY, $user->session_version);

        return redirect()->route('filament.admin.pages.change-password')
            ->with('password_status', 'Password berhasil diperbarui.');
    }
}
