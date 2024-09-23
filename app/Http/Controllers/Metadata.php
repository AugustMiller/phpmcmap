<?php

namespace App\Http\Controllers;

use App\Helpers\Coordinates;
use App\Helpers\Data;
use App\Helpers\Math;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class Metadata extends Controller
{
    public function level()
    {
        $fs = Storage::disk('misc');
        $nbt = Data::parseNbt($fs->get('level.dat'));

        return response()->json($nbt->jsonSerialize());
    }

    public function players()
    {
        $fs = Storage::disk('misc');
        $files = collect($fs->files('playerdata'))->reject(fn($f) => str_ends_with($f, 'dat_old'));

        $players = [];

        foreach ($files as $file) {
            /** @var Aternos\Nbt\Tag\CompoundTag $data */
            $data = Data::parseNbt($fs->get($file));
            $player = [];

            $uuid = Data::convertIntArrayToUuid($data->getIntArray('UUID'));

            $player['uuid'] = $uuid;
            $player['name'] = Data::getPlayerNameByUuid($uuid);
            $player['position'] = $data->getList('Pos');
            $player['dimension'] = $data->getString('Dimension');

            $players[] = $player;
        }

        return response()->json($players);
    }

    public function block(int $x, int $z, int $y, Request $request)
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
