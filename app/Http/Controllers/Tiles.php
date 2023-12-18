<?php

namespace App\Http\Controllers;

use App\Exceptions\RegionDataMissingException;
use App\Helpers\Coordinates;
use App\Helpers\Math;
use App\Models\Tile;
use App\Models\Vector;
use Illuminate\Routing\Controller;
use ImagickPixel;

class Tiles extends Controller
{
    public function render(int $zoom, int $x, int $y)
    {
        try {
            $region = Coordinates::tileToRegion($zoom, $x, $y);
        } catch (RegionDataMissingException $e) {
            return response()
                ->streamDownload(function () {
                    $tile = new Tile('No region data.');

                    $tile->writeMetadata();

                    echo $tile->getImage()->getImageBlob();
                }, 'tile.png', ['Content-Type' => 'image/png'], 'inline');
        }

        $edge = Coordinates::chunksPerTile($zoom);
        $tilesPerRegion = Coordinates::tilesPerRegion($zoom);
        $offsetInRegion = new Vector(
            Math::modPositive($x, $tilesPerRegion) * $edge,
            Math::modPositive($y, $tilesPerRegion) * $edge,
        );

        $chunks = $region->getChunksFrom(
            $offsetInRegion->x,
            $offsetInRegion->y,
            $edge,
            $edge,
        );

        $chunksWithData = $chunks
            ->filter(fn($c) => $c->getDataLength());

        $latestModificationDate = $chunksWithData
            ->map(fn($c) => $c->getLastModified())
            ->max();

        $date = $latestModificationDate ? $latestModificationDate->format('c') : 'No data';

        $tile = new Tile(<<<TXT
{$region->fileName()}
{$edge} chunk(s) per edge
{$chunksWithData->count()} chunk(s) with data
Offset: {$offsetInRegion->absX()}, {$offsetInRegion->absY()}
Most recently modified at:
{$date}
TXT);

        foreach ($chunksWithData as $chunk) {
            /** @var Chunk $c */
            $tile->draw(function($d) use ($chunk, $offsetInRegion, $edge) {
                /** @var ImagickDraw $d */

                $unit = Tile::WIDTH / $edge;

                $d->setStrokeColor(new ImagickPixel('#9ABCDE'));
                $d->setFillColor(new ImagickPixel('#ABCDEF'));
                $d->rectangle(
                    ($chunk->x - $offsetInRegion->x) * $unit,
                    ($chunk->z - $offsetInRegion->y) * $unit,
                    ($chunk->x - $offsetInRegion->x) * $unit + $unit,
                    ($chunk->z - $offsetInRegion->y) * $unit + $unit,
                );

                return $d;
            });
        }

        $tile->writeMetadata();

        return response()
            ->streamDownload(function () use ($tile) {
                echo $tile->getImage()->getImageBlob();
            }, 'tile.png', ['Content-Type' => 'image/png'], 'inline');
    }
}
