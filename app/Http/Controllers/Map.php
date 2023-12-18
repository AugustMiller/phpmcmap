<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Map extends Controller
{
    public function __invoke(Request $request)
    {
        return view('map', [
            'x' => $request->query('x', 0),
            'y' => $request->query('y', 0),
            'zoom' => $request->query('zoom', 0),
        ]);
    }
}
