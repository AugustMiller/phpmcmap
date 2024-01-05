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
                ->view('svg.tile-error', [
                    'message' => $e->getMessage(),
                ])
                ->header('Content-Type', 'image/svg+xml');
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

        // Start a buffer of rectangles to draw into the final image:
        $rects = [];

        $chunkUnit = Tile::WIDTH / $edge;
        $blockUnit = $chunkUnit / 16;

        foreach ($chunksWithData as $chunk) {
            /** @var Chunk $chunk */

            try {
                $surface = $chunk->expandHeightmap(Chunk::NBT_TAG_HEIGHTMAP_MOTION_BLOCKING);
                $ocean = $chunk->expandHeightmap(Chunk::NBT_TAG_HEIGHTMAP_OCEAN_FLOOR);
            } catch (\Throwable $e) {
                Log::error("Failed to load heightmap: {$e->getMessage()}");

                // Skip this chunk:
                continue;
            }

            $chunkX = ($chunk->x - $offsetInRegion->x) * $chunkUnit;
            $chunkZ = ($chunk->z - $offsetInRegion->z) * $chunkUnit;

            foreach ($surface as $i => $surfaceHeight) {
                $oceanHeight = $ocean[$i];

                $color = [
                    'r' => 0,
                    'g' => 0,
                    'b' => 0,
                ];

                $block = new Vector(
                    x: ($i % 16) * $blockUnit,
                    z: floor($i / 16) * $blockUnit,
                    y: $surfaceHeight,
                );

                // Detect water bodies and simulate depth:
                if ($surfaceHeight > $oceanHeight) {
                    $depth = $surfaceHeight - $oceanHeight;
                    $clamped = sqrt(min(64, $depth));

                    $color['r'] = Math::scale($clamped, 0, 16, 64, 0);
                    $color['g'] = Math::scale($clamped, 0, 16, 64, 0);
                    $color['b'] = Math::scale($clamped, 0, 16, 200, 0);
                }

                // Everything else should be treated as land:
                if ($surfaceHeight === $oceanHeight) {
                    $value = Math::scale($block->y, 0, 255, 100, 240);

                    $color['r'] = min(255, $value + 30);
                    $color['g'] = min(255, $value + 30);
                    $color['b'] = $value;
                }

                // Round color values to 
                $color = array_map('floor', $color);

                $rect = [
                    'x' => $chunkX + $block->x,
                    'y' => $chunkZ + $block->z,
                    'width' => $blockUnit,
                    'height' => $blockUnit,
                    'color' => "rgb({$color['r']}, {$color['g']}, {$color['b']})",
                ];

                $rects[] = $rect;
            }
        }

        return response()
            ->view('svg.tile', [
                'rects' => $rects,
                'title' => "Map tile {$x}, {$z} at zoom level {$zoom}",
            ])
            ->header('Content-Type', 'image/svg+xml')
            // Send some metadata with each response:
            ->header('X-MC-Region', "{$region->x}, {$region->z}")
            ->header('X-MC-Chunks', "{$offsetInRegion->x}, {$offsetInRegion->z} to {$offsetInRegion->x}")
            ->header('X-MC-Data', "{$chunks->count()}/{$chunksWithData->count()}")
            ->header('X-MC-Last-Modified', $date);
    }
}
