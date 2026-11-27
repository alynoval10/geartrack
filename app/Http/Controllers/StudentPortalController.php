<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\StudentLoanRequest;
use App\Services\LoanAssetEligibility;
use App\Services\StudentLoanRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentPortalController extends Controller
{
    public function index(Request $request, LoanAssetEligibility $eligibleAssets): View
    {
        $account = $request->user('student');
        $borrower = $account->currentBorrower();
        abort_unless($borrower, 403, 'Data siswa tidak aktif pada tahun ajaran berjalan.');

        $selectedAssetIds = collect(old('asset_ids', []))
            ->filter(fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
        $assets = $selectedAssetIds->isEmpty()
            ? collect()
            : $eligibleAssets->query()
                ->whereIn('id', $selectedAssetIds)
                ->orderBy('asset_code')
                ->get()
                ->map(fn (Asset $asset): array => [
                    'id' => $asset->id,
                    'label' => $asset->asset_code.' — '.$asset->name,
                    'details' => collect([$asset->category?->name, $asset->location?->fullName()])->filter()->implode(' · '),
                ]);

        return view('student.dashboard', [
            'account' => $account,
            'borrower' => $borrower,
            'qrCode' => QrCode::format('svg')
                ->size(240)
                ->margin(1)
                ->errorCorrection('M')
                ->generate(route('borrowers.qr-card', ['token' => $borrower->qr_token])),
            'assets' => $assets,
            'requests' => StudentLoanRequest::query()
                ->where('student_account_id', $account->id)
                ->with(['items', 'loan'])
                ->latest()
                ->limit(30)
                ->get(),
            'defaultDueDate' => today()->addDays(7)->toDateString(),
        ]);
    }

    public function submitLoanRequest(Request $request, StudentLoanRequestService $service): RedirectResponse
    {
        $account = $request->user('student');
        $borrower = $account->currentBorrower();
        abort_unless($borrower, 403, 'Data siswa tidak aktif pada tahun ajaran berjalan.');

        $validated = Validator::make($request->all(), [
            'asset_ids' => ['required', 'array', 'min:1', 'max:100'],
            'asset_ids.*' => ['required', 'integer', 'distinct', 'exists:assets,id'],
            'purpose' => ['required', 'string', 'max:5000'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ], [
            'asset_ids.required' => 'Pilih setidaknya satu perangkat.',
            'asset_ids.min' => 'Pilih setidaknya satu perangkat.',
            'asset_ids.max' => 'Maksimal 100 perangkat dalam satu permintaan.',
        ])->validate();

        $service->submit($account, $borrower, $validated);

        return redirect()->route('student.dashboard')->with('status', 'Permintaan peminjaman dikirim dan menunggu persetujuan guru atau administrator.');
    }

    public function scanAsset(Request $request, LoanAssetEligibility $eligibleAssets): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:100'],
        ]);
        $asset = $eligibleAssets->findByQrToken($validated['token']);

        if (! $asset) {
            return response()->json([
                'message' => 'Aset tidak ditemukan atau saat ini tidak tersedia untuk diajukan.',
            ], 422);
        }

        return response()->json([
            'id' => $asset->id,
            'label' => $eligibleAssets->label($asset),
            'message' => $asset->asset_code.' berhasil ditambahkan ke pilihan perangkat.',
        ]);
    }
}
