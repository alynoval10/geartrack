<?php

namespace App\Http\Controllers;

use App\Models\AssetTransfer;
use App\Models\SchoolSetting;
use Illuminate\Http\Response;

class AssetTransferDocumentController extends Controller
{
    public function __invoke(AssetTransfer $transfer): Response
    {
        $transfer->load('items');

        $school = SchoolSetting::current();

        return response()->view('transfers.document', compact('transfer', 'school'))
            ->header('Cache-Control', 'private, no-store');
    }
}
