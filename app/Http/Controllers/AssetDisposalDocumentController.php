<?php

namespace App\Http\Controllers;

use App\Models\AssetDisposal;
use App\Models\SchoolSetting;
use Illuminate\Http\Response;

class AssetDisposalDocumentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AssetDisposal $disposal): Response
    {
        abort_unless($disposal->status === 'approved', 404);

        $disposal->load('items');

        return response()->view('asset-disposals.document', [
            'disposal' => $disposal,
            'school' => SchoolSetting::current(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
