<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\View\View;

class LocationLabelController extends Controller
{
    public function __invoke(Location $location): View
    {
        $location->loadMissing('parent');

        return view('locations.label', compact('location'));
    }
}
