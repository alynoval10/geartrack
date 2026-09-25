<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryReportRequest;
use App\Services\InventoryReportService;
use App\Services\SpreadsheetService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryReportController extends Controller
{
    public function __invoke(InventoryReportRequest $request, InventoryReportService $reports, SpreadsheetService $spreadsheets): Response|BinaryFileResponse
    {
        $data = $reports->generate($request->validated());
        if ($request->validated('format') === 'print') {
            return response()->view('reports.print', $data)->header('Cache-Control', 'private, no-store');
        }
        $path = $spreadsheets->write(['Laporan' => [$data['headers'], ...$data['rows']], 'Ringkasan' => [['Laporan', $data['title']], ['Periode', $data['period']], ['Ringkasan', $data['summary']], ['Dibuat', now()->format('Y-m-d H:i')]]]);

        return response()->download($path, 'geartrack-'.$request->validated('type').'-'.now()->format('Ymd-His').'.xlsx', ['Cache-Control' => 'private, no-store'])->deleteFileAfterSend();
    }
}
