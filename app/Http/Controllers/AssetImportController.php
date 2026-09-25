<?php

namespace App\Http\Controllers;

use App\Services\AssetImportService;
use App\Services\SpreadsheetService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AssetImportController extends Controller
{
    public function template(AssetImportService $imports, SpreadsheetService $spreadsheets): BinaryFileResponse
    {
        $path = $spreadsheets->write($imports->template());

        return response()->download($path, 'template-impor-aset-geartrack.xlsx', ['Cache-Control' => 'private, no-store'])->deleteFileAfterSend();
    }

    public function preview(Request $request, AssetImportService $imports): RedirectResponse
    {
        $request->session()->forget('asset_import');
        $validator = validator($request->all(), ['file' => ['required', 'file', 'max:5120', 'extensions:xlsx', 'mimes:xlsx,zip']]);
        if ($validator->fails()) {
            return $this->back()->withErrors($validator);
        }
        try {
            $preview = $imports->preview($request->file('file')->getRealPath());
        } catch (ValidationException $exception) {
            return $this->back()->withErrors($exception->errors());
        }
        $request->session()->put('asset_import', [...$preview, 'token' => (string) Str::uuid(), 'user_id' => $request->user()->id, 'expires_at' => now()->addMinutes(30)->timestamp]);

        return $this->back();
    }

    public function store(Request $request, AssetImportService $imports): RedirectResponse
    {
        $draft = $request->session()->get('asset_import');
        if (! is_array($draft) || $draft['user_id'] !== $request->user()->id || $draft['expires_at'] < now()->timestamp
            || ! is_string($request->input('token')) || ! hash_equals($draft['token'], $request->input('token'))) {
            return $this->back()->withErrors(['file' => 'Pratinjau tidak tersedia atau sudah kedaluwarsa. Unggah file kembali.']);
        }
        if ($draft['errors'] !== [] || ! $request->boolean('confirm')) {
            return $this->back()->withErrors(['file' => 'Perbaiki seluruh baris dan centang konfirmasi sebelum menyimpan.']);
        }
        try {
            $count = $imports->import($draft['rows']);
        } catch (ValidationException $exception) {
            return $this->back()->withErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return $this->back()->withErrors(['file' => 'Impor gagal. Tidak ada aset yang disimpan. Unggah ulang untuk memeriksa data terbaru.']);
        }
        $request->session()->forget('asset_import');
        Notification::make()->title($count.' aset berhasil diimpor')->success()->duration(5000)->send();

        return $this->back();
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('asset_import');

        return $this->back();
    }

    private function back(): RedirectResponse
    {
        return redirect()->route('filament.admin.pages.import-assets', [], 303);
    }
}
