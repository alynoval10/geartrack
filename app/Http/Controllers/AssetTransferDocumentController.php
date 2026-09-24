<?php

namespace App\Http\Controllers;

use App\Models\AssetTransfer;
use Illuminate\Http\Response;

class AssetTransferDocumentController extends Controller
{
    public function __invoke(AssetTransfer $transfer): Response
    {
        $transfer->load('items');

        return response()->view('transfers.document', compact('transfer'))
            ->header('Cache-Control', 'private, no-store');
    }
}
