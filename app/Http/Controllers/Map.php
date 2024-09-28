<?php

namespace App\Http\Controllers;

use App\Helpers\Data;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class Map extends Controller
{
    public function __invoke(Request $request)
    {
        $fs = Storage::disk('misc');
        /** @var \Aternos\Nbt\Tag\CompoundTag $nbt */
        $nbt = Data::parseNbt($fs->get('level.dat'));
        $data = $nbt->getCompound('Data');

        return view('map', [
            'spawnX' => $data->getInt('SpawnX')->getValue(),
            'spawnZ' => $data->getInt('SpawnZ')->getValue(),
        ]);
    }
}
