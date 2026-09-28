<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryReportRequest;
use App\Models\SchoolSetting;
use App\Services\InventoryReportService;
use App\Services\SpreadsheetService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryReportController extends Controller
{
    public function __invoke(InventoryReportRequest $request, InventoryReportService $reports, SpreadsheetService $spreadsheets): Response|BinaryFileResponse
    {
        $data = $reports->generate($request->validated());
        $data['school'] = SchoolSetting::current();
        if ($request->validated('format') === 'print') {
            return response()->view('reports.print', $data)->header('Cache-Control', 'private, no-store');
        }
        $path = $spreadsheets->write(['Laporan' => [$data['headers'], ...$data['rows']], 'Ringkasan' => [['Sekolah', $data['school']->school_name], ['Tahun Ajaran', $data['school']->academic_year], ['Laporan', $data['title']], ['Periode', $data['period']], ['Ringkasan', $data['summary']], ['Penandatangan', $data['school']->report_signer_name], ['Jabatan', $data['school']->report_signer_title], ['Dibuat', now()->format('Y-m-d H:i')]]]);

        return response()->download($path, 'geartrack-'.$request->validated('type').'-'.now()->format('Ymd-His').'.xlsx', ['Cache-Control' => 'private, no-store'])->deleteFileAfterSend();
    }
}
