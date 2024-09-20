<?php

namespace App\Http\Controllers;

use App\Helpers\Color;
use App\Helpers\Coordinates;
use App\Helpers\Math;
use App\Models\DbChunk;
use App\Models\DbRegion;
use App\Models\Vector;
use Illuminate\Routing\Controller;

/**
 * Tiles Controller
 * 
 * Responsible for preparing data for individual tile SVG documents.
 * 
 * The minimum zoom level (0) will return a tile that represents an entire region file, so there is at most one region file open per request. The {@see App\Helpers\Coordinates} class contains logic for translating different types of coordinates according to the Minecraft Anvil storage format.
 */
class Tiles extends Controller
{
    public const TILE_WIDTH = 256;

    public function render(int $zoom, int $x, int $z)
    {
        $region = Coordinates::tileToRegion($zoom, $x, $z);
        $dbRegion = DbRegion::firstWhere([
            'x' => $region->x,
            'z' => $region->z,
        ]);

        if (!$dbRegion) {
            return response()
                ->view('svg.tile-error', [
                    'message' => "No data exists for region [{$x}, {$z}].",
                    'x' => $x,
                    'z' => $z,
                ])
                ->header('Content-Type', 'image/svg+xml');
        }

        $edge = Coordinates::chunksPerTile($zoom);
        $tilesPerRegion = Coordinates::tilesPerRegion($zoom);
        $offsetInRegion = new Vector(
            x: Math::modPositive($x, $tilesPerRegion) * $edge,
            z: Math::modPositive($z, $tilesPerRegion) * $edge,
        );

        $chunks = $dbRegion->chunksFrom(
            $offsetInRegion->x,
            $offsetInRegion->z,
            $edge,
            $edge,
        )->get();

        // Filter out chunks without heightmap data:
        $chunks = $chunks->filter(function ($c) {
            return $c->hasHeightmaps();
        });

        // From those, grab the earliest modified time (we'll send this as a header, later):
        $lastModified = $chunks->min('last_modified');

        // Start a buffer of rectangles to draw into the final image:
        $rects = [];

        $chunkUnit = self::TILE_WIDTH / $edge;
        $blockUnit = $chunkUnit / 16;

        foreach ($chunks as $chunk) {
            /** @var DbChunk $chunk */

            $surface = $chunk->heightmap_motion_blocking;
            $ocean = $chunk->heightmap_ocean_floor;

            // Set base offsets so blocks in the heightmap can be drawn in the correct location:
            $chunkX = ($chunk->x - $offsetInRegion->x) * $chunkUnit;
            $chunkZ = ($chunk->z - $offsetInRegion->z) * $chunkUnit;

            // We'll handle rendering a little differently when zoomed out:
            if ($zoom < 3) {
                $avgSurfaceHeight = $surface->average();
                $avgOceanDepth = $ocean->average();

                $isWater = $avgOceanDepth < $avgSurfaceHeight;

                $rect = [
                    'x' => $chunkX,
                    'y' => $chunkZ,
                    'width' => $chunkUnit,
                    'height' => $chunkUnit,
                    'color' => 'tan',
                ];

                if ($isWater) {
                    $depth = $avgSurfaceHeight - $avgOceanDepth;
                    $clamped = sqrt(min(64, $depth));

                    $rect['color'] = new Color(
                        Math::scale($clamped, 0, 16, 64, 0),
                        Math::scale($clamped, 0, 16, 64, 0),
                        Math::scale($clamped, 0, 16, 200, 0),
                    );
                } else {
                    $value = Math::scale($avgSurfaceHeight, 0, 255, 100, 240);

                    $rect['color'] = new Color(
                        min(255, $value + 30),
                        min(255, $value + 30),
                        $value,
                    );
                }

                $rects[] = $rect;

                // Nothing else to process, here!
                continue;
            }

            foreach ($surface as $i => $surfaceHeight) {
                // Load the same index from the ocean_floor heightmap:
                $oceanHeight = $ocean[$i];

                $color = new Color;

                // Heightmaps are stored as one-dimensional arrays, one row after another. You can get the X and Z coordinate for each elevation reading via the remainder after division:
                $block = new Vector(
                    x: ($i % 16) * $blockUnit,
                    z: floor($i / 16) * $blockUnit,
                    y: $surfaceHeight,
                );

                // Detect water bodies and simulate depth:
                if ($surfaceHeight > $oceanHeight) {
                    $depth = $surfaceHeight - $oceanHeight;
                    $clamped = sqrt(min(64, $depth));

                    $color->r = Math::scale($clamped, 0, 16, 64, 0);
                    $color->g = Math::scale($clamped, 0, 16, 64, 0);
                    $color->b = Math::scale($clamped, 0, 16, 200, 0);
                }

                // Everything else should be treated as land:
                if ($surfaceHeight === $oceanHeight) {
                    $value = Math::scale($block->y, 0, 255, 100, 240);

                    $color->r = min(255, $value + 30);
                    $color->g = min(255, $value + 30);
                    $color->b = $value;
                }

                $rect = [
                    'x' => $chunkX + $block->x,
                    'y' => $chunkZ + $block->z,
                    'width' => $blockUnit,
                    'height' => $blockUnit,
                    // Round color values to simplify SVG output:
                    'color' => $color->round(),
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
            ->header('X-MC-Chunks', "{$edge}x{$edge} from [{$offsetInRegion->x}, {$offsetInRegion->z}]")
            ->header('X-MC-Data', $chunks->count())
            ->header('X-MC-Last-Modified', $lastModified);
    }
}
