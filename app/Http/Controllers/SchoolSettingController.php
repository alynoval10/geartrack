<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolSettingController extends Controller
{
    /**
     * Menyimpan satu identitas resmi yang dipakai seluruh laporan dan dokumen.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'principal_name' => ['nullable', 'string', 'max:150'],
            'principal_nip' => ['nullable', 'string', 'max:50'],
            'asset_manager_name' => ['nullable', 'string', 'max:150'],
            'asset_manager_nip' => ['nullable', 'string', 'max:50'],
            'academic_year' => ['nullable', 'string', 'max:30'],
            'report_signer_name' => ['nullable', 'string', 'max:150'],
            'report_signer_title' => ['nullable', 'string', 'max:100'],
            'report_signer_nip' => ['nullable', 'string', 'max:50'],
        ]);

        $setting = SchoolSetting::current();
        if ($request->hasFile('logo')) {
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $data['logo'] = $request->file('logo')->store('school', 'public');
        }
        $setting->update($data);

        return redirect()->route('filament.admin.pages.school-settings')
            ->with('school_settings_status', 'Identitas sekolah berhasil disimpan.');
    }
}
