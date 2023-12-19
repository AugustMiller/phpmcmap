<?php

namespace App\Http\Controllers;

use App\Helpers\Coordinates;
use App\Helpers\Math;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Metadata extends Controller
{
    public function blockInfo(int $x, int $z, int $y, Request $request)
    {
        $chunk = Coordinates::blockToChunk($x, $z);
        $block = $chunk->getBlock(
            Math::modPositive($x, 16),
            Math::modPositive($z, 16),
            $y,
        );

        return 'Hello!';
    }
}
