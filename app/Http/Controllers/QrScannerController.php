<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class QrScannerController extends Controller
{
    public function index(): View
    {
        return view('scanner.index');
    }
}