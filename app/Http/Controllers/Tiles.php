<?php

namespace App\Http\Controllers;

use App\Exceptions\RegionDataMissingException;
use App\Helpers\Coordinates;
use App\Helpers\Math;
use App\Models\Chunk;
use App\Models\Tile;
use App\Models\Vector;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickPixel;

/**
 * Tiles Controller
 * 
 * Responsible for rendering individual tile PNGs.
 * 
 * The minimum zoom level (0) will return a tile that represents an entire region file, so there is at most one region file open per request. The {@see App\Helpers\Coordinates} class contains logic for translating different types of coordinates according to the Minecraft Anvil storage format.
 */
class Tiles extends Controller
{
    public function render(int $zoom, int $x, int $z)
    {
        try {
            $region = Coordinates::tileToRegion($zoom, $x, $z);
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
            x: Math::modPositive($x, $tilesPerRegion) * $edge,
            z: Math::modPositive($z, $tilesPerRegion) * $edge,
        );

        $chunks = $region->getChunksFrom(
            $offsetInRegion->x,
            $offsetInRegion->z,
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
{$chunks->count()}/{$chunksWithData->count()} chunk(s) have data
Offset: {$offsetInRegion->x}, {$offsetInRegion->z}
Most recently modified at:
{$date}
TXT);

        foreach ($chunksWithData as $chunk) {
            /** @var Chunk $chunk */
            $tile->draw(function($d) use ($chunk, $offsetInRegion, $edge, $zoom) {
                /** @var ImagickDraw $d */

                try {
                    $heightmap = $chunk->expandHeightmap(Chunk::NBT_TAG_HEIGHTMAP_MOTION_BLOCKING);
                } catch (\Throwable $e) {
                    Log::error("Failed to load heightmap: {$e->getMessage()}");

                    // Return empty canvas:
                    return;
                }

                $chunkUnit = Tile::WIDTH / $edge;
                $blockUnit = $chunkUnit / 16;

                $chunkX = ($chunk->x - $offsetInRegion->x) * $chunkUnit;
                $chunkZ = ($chunk->z - $offsetInRegion->z) * $chunkUnit;

                if ($zoom <= 2) {
                    $color = new ImagickPixel('#0000FF');
                    $r = Math::scale($heightmap->average(), 0, 256, 0, 1);
                    $color->setColorValue(Imagick::COLOR_RED, $r);
                    $d->setFillColor($color);

                    $d->rectangle(
                        $chunkX,
                        $chunkZ,
                        $chunkX + $chunkUnit,
                        $chunkZ + $chunkUnit,
                    );

                    return $d;
                }

                foreach ($heightmap as $i => $height) {
                    $block = new Vector(
                        x: ($i % 16) * $blockUnit,
                        z: floor($i / 16) * $blockUnit,
                        y: $height,
                    );

                    $color = new ImagickPixel('#000000');
                    $g = Math::scale($block->y, 50, 200, 0, 1);
                    $color->setColorValue(Imagick::COLOR_GREEN, $g);
                    $d->setFillColor($color);

                    $d->rectangle(
                        $chunkX + $block->x,
                        $chunkZ + $block->z,
                        $chunkX + $block->x + $blockUnit,
                        $chunkZ + $block->z + $blockUnit,
                    );
                }

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
