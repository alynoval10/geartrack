<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class QrScannerController extends Controller
{
    public function index(Request $request): View
    {
        $stockTakeId = filter_var($request->query('stock_take'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;

        return view('scanner.index', compact('stockTakeId'));
    }
}
