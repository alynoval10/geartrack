<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use App\Models\SchoolSetting;
use App\Models\StudentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StudentAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('student')->check()) {
            return redirect()->route('student.dashboard');
        }

        return view('student.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'nis' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $academicYear = SchoolSetting::query()->value('academic_year');
        $hasActiveBorrower = Borrower::query()
            ->where('type', 'student')
            ->where('nis', $credentials['nis'])
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->exists();

        if (! $hasActiveBorrower) {
            return back()
                ->withInput($request->only('nis'))
                ->withErrors(['nis' => 'NIS atau kata sandi tidak sesuai, atau siswa tidak aktif pada tahun ajaran berjalan.']);
        }

        StudentAccount::query()->firstOrCreate(
            ['nis' => $credentials['nis']],
            ['password' => Hash::make($credentials['nis']), 'must_change_password' => true],
        );

        if (! Auth::guard('student')->attempt($credentials)) {
            return back()
                ->withInput($request->only('nis'))
                ->withErrors(['nis' => 'NIS atau kata sandi tidak sesuai, atau siswa tidak aktif pada tahun ajaran berjalan.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('student.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();
        $request->session()->regenerate();

        return redirect()->route('student.login');
    }

    public function editPassword(): View
    {
        return view('student.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'current_password' => ['required', 'current_password:student'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi awal.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ])->validate();

        $student = $request->user('student');
        $student->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('student.dashboard')->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
