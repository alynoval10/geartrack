<?php

namespace App\Filament\Pages;

use App\Models\Borrower;
use App\Models\SchoolSetting;
use App\Models\StudentAccount;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function mount(): void
    {
        if (Auth::guard('student')->check()) {
            redirect()->route('student.dashboard');

            return;
        }

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
        $data = $this->form->getState();
        $identifier = trim((string) ($data['email'] ?? ''));

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->authenticateStaff($data);
        }

        $this->authenticateStudent($identifier, (string) ($data['password'] ?? ''));

        return null;
    }

    /**
     * Authenticate staff with the existing Filament email flow.
     *
     * @param  array<string, mixed>  $data
     */
    private function authenticateStaff(array $data): ?LoginResponse
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

    private function authenticateStudent(string $nis, #[SensitiveParameter] string $password): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $academicYear = SchoolSetting::query()->value('academic_year');
        $isActiveStudent = Borrower::query()
            ->where('type', 'student')
            ->where('nis', $nis)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->exists();

        if (! $isActiveStudent) {
            $this->throwStudentLoginFailure();
        }

        StudentAccount::query()->firstOrCreate(
            ['nis' => $nis],
            ['password' => Hash::make($nis), 'must_change_password' => true],
        );

        if (! Auth::guard('student')->attempt(['nis' => $nis, 'password' => $password])) {
            $this->throwStudentLoginFailure();
        }

        request()->session()->regenerate();
        $this->redirect(route('student.dashboard'));
    }

    private function throwStudentLoginFailure(): never
    {
        throw ValidationException::withMessages([
            'data.email' => 'NIS atau kata sandi tidak sesuai, atau siswa tidak aktif pada tahun ajaran berjalan.',
        ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email atau NIS')
            ->placeholder('Masukkan email staf atau NIS siswa')
            ->required()
            ->autocomplete()
            ->autofocus();
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
