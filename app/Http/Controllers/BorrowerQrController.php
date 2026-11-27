<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BorrowerQrController extends Controller
{
    public function show(string $token, Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $borrower = Borrower::query()
            ->where('type', 'student')
            ->where('qr_token', $token)
            ->firstOrFail();

        $url = route('borrowers.qr-card', ['token' => $borrower->qr_token]);
        $qrCode = QrCode::format('svg')
            ->size(320)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($url);

        return view('borrowers.qr-card', [
            'borrower' => $borrower,
            'schoolName' => SchoolSetting::current()->school_name,
            'qrCode' => $qrCode,
        ]);
    }

    public function printGroup(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'level' => ['required', 'string', 'in:X,XI,XII'],
            'class_name' => ['nullable', 'string', 'max:100'],
        ]);
        $academicYear = SchoolSetting::query()->value('academic_year');
        $className = filled($data['class_name'] ?? null) ? $data['class_name'] : null;
        $borrowers = blank($academicYear)
            ? collect()
            : Borrower::query()
                ->where('type', 'student')
                ->where('is_active', true)
                ->where('academic_year', $academicYear)
                ->where('class_level', $data['level'])
                ->when($className, fn ($query) => $query->where('class_name', $className))
                ->orderBy('class_name')
                ->orderBy('name')
                ->get();

        $qrCards = $borrowers->map(fn (Borrower $borrower): array => [
            'borrower' => $borrower,
            'qrCode' => QrCode::format('svg')
                ->size(240)
                ->margin(1)
                ->errorCorrection('M')
                ->generate(route('borrowers.qr-card', ['token' => $borrower->qr_token])),
        ]);

        return view('borrowers.qr-cards', [
            'qrCards' => $qrCards,
            'schoolName' => SchoolSetting::query()->value('school_name'),
            'academicYear' => $academicYear,
            'classLevel' => $data['level'],
            'className' => $className,
            'backUrl' => route('filament.admin.resources.borrowers.index'),
        ]);
    }

    public function printLogins(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'level' => ['required', 'string', 'in:X,XI,XII'],
            'class_name' => ['nullable', 'string', 'max:100'],
        ]);
        $academicYear = SchoolSetting::query()->value('academic_year');
        $className = filled($data['class_name'] ?? null) ? $data['class_name'] : null;
        $borrowers = blank($academicYear)
            ? collect()
            : Borrower::query()
                ->with('studentAccount')
                ->where('type', 'student')
                ->where('is_active', true)
                ->where('academic_year', $academicYear)
                ->where('class_level', $data['level'])
                ->when($className, fn ($query) => $query->where('class_name', $className))
                ->orderBy('class_name')
                ->orderBy('name')
                ->get();

        return view('borrowers.student-logins', [
            'borrowers' => $borrowers,
            'schoolName' => SchoolSetting::query()->value('school_name'),
            'academicYear' => $academicYear,
            'classLevel' => $data['level'],
            'className' => $className,
            'loginUrl' => route('filament.admin.auth.login'),
        ]);
    }
}
