<?php

namespace App\Http\Controllers;

use App\Helpers\Data;
use App\Models\DbPoi;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class Metadata extends Controller
{
    public function level(Request $request)
    {
        $fs = Storage::disk('misc');
        $nbt = Data::parseNbt($fs->get('level.dat'));

        return response()->json($nbt->jsonSerialize());
    }

    public function players(Request $request)
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
            $player['lastActivity'] = $fs->lastModified($file);

            $death = $data->getCompound('LastDeathLocation');

            if ($death) {
                $player['lastDeath'] = [
                    'dimension' => $death->getString('dimension'),
                    'position' => $death->getIntArray('pos'),
                ];
            }

            $players[] = $player;
        }

        return response()->json($players);
    }

    public function poi(Request $request, int $x1, int $z1, int $x2, int $z2)
    {
        $type = $request->query('type');

        $poiQuery = DbPoi::query()
            ->whereBetween('x', [min($x1, $x2), max($x1, $x2)])
            ->whereBetween('z', [min($z1, $z2), max($z1, $z2)]);

        if ($type) {
            $poiQuery->where('entity_type', "minecraft:{$type}");
        }

        return response()->json($poiQuery->get());
    }
}
