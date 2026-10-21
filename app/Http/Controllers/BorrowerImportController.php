<?php

namespace App\Http\Controllers;

use App\Services\BorrowerImportService;
use App\Services\SpreadsheetService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BorrowerImportController extends Controller
{
    public function template(Request $request, BorrowerImportService $imports, SpreadsheetService $spreadsheets): BinaryFileResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $path = $spreadsheets->write($imports->template());

        return response()->download($path, 'template-impor-data-peminjam-geartrack.xlsx', ['Cache-Control' => 'private, no-store'])->deleteFileAfterSend();
    }
}
