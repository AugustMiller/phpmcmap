<?php

namespace App\Http\Controllers;

use App\Helpers\Color;
use App\Helpers\Coordinates;
use App\Helpers\Math;
use App\Models\Chunk;
use App\Models\DbChunk;
use App\Models\DbRegion;
use App\Models\Vector;
use Illuminate\Routing\Controller;
// use Illuminate\Support\Facades\Cache;

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
        /** @var DbRegion $dbRegion */
        $dbRegion = DbRegion::firstWhere([
            'x' => $region->x,
            'z' => $region->z,
        ]);

        if (!$dbRegion) {
            return response()
                ->view('svg.tile-error', [
                    'message' => "No data exists for region [{$region->x}, {$region->z}].",
                    'x' => $region->x,
                    'z' => $region->z,
                ])
                ->header('Content-Type', 'image/svg+xml');
        }

        // Let’s see how long this takes:
        $timeStart = microtime(true);

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

        $timeLoad = microtime(true) - $timeStart;

        // From those, grab the earliest modified time (we'll send this as a header, later):
        $lastModified = $chunks->min('last_modified');

        // Start a buffer of rectangles to draw into the final image:
        $rects = [];

        $sampleResolution = Chunk::BLOCK_DIMENSIONS / pow(2, $zoom - 1);
        /** @var int $chunkWidth Dimension in pixels of a chunk. */
        $chunkWidth = self::TILE_WIDTH / $edge;
        /** @var int $blockUnit Dimension in pixels of a single block. */
        $blockUnit = 16;

        // echo "Chunks: {$chunks->count()}\n";
        // echo "Edge: {$edge}\n";
        // echo "Chunk width: {$chunkWidth}px\n";
        // echo "Block Unit: {$blockUnit}px\n";
        // echo "Sample Resolution: {$sampleResolution}\n";
        // echo "Blocks per chunk: " . Chunk::BLOCK_DIMENSIONS / $sampleResolution . "\n";
        // die;

        foreach ($chunks as $chunk) {
            /** @var DbChunk $chunk */

            // Do we have data to work with?
            if (!$chunk->hasHeightmaps()) {
                continue;
            }

            // Set base offsets so blocks in the heightmap can be drawn in the correct location:
            $chunkX = ($chunk->x - $offsetInRegion->x) * $chunkWidth;
            $chunkZ = ($chunk->z - $offsetInRegion->z) * $chunkWidth;

            // Load up the completed heightmap data—we'll need them for rendering individual blocks!
            $surface = $chunk->heightmap_motion_blocking;
            $ocean = $chunk->heightmap_ocean_floor;

            foreach ($surface as $i => $surfaceHeight) {
                $blockX = $i % Chunk::BLOCK_DIMENSIONS;
                $blockZ = floor($i / Chunk::BLOCK_DIMENSIONS);

                // We may need to sample the data at a lower resolution:
                if (($i % $sampleResolution) !== 0 || (floor($i / Chunk::BLOCK_DIMENSIONS) % $sampleResolution) !== 0) {
                    continue;
                }

                // Heightmaps are stored as one-dimensional arrays, one row after another.
                // These coordinates are *local* to the chunk, fow now!
                // You can get the X and Z coordinate for each elevation reading via the remainder after division:
                $blockOffsetX = ($blockX * $blockUnit) / $sampleResolution;
                $blockOffsetZ = ($blockZ * $blockUnit) / $sampleResolution;

                // Load the same index from the ocean_floor heightmap:
                $oceanHeight = $ocean[$i];

                $color = new Color;

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
                    $value = Math::scale($surfaceHeight, 0, 255, 0, 200);

                    $color->r = max(0, $value - 20);
                    $color->g = min(255, $value + 30);
                    $color->b = $value;
                }

                $rect = [
                    'x' => $chunkX + $blockOffsetX,
                    'y' => $chunkZ + $blockOffsetZ,
                    'width' => $blockUnit,
                    'height' => $blockUnit,
                    // Round color values to simplify SVG output:
                    'color' => $color->round(),
                ];

                $rects[] = $rect;
            }
        }

        $timeCompute = microtime(true) - $timeStart;

        $svg = view('svg.tile', [
            'rects' => $rects,
            'title' => "Map tile {$x}, {$z} at zoom level {$zoom}",
        ])->render();

        $timeRender = microtime(true) - $timeStart;

        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            // Send some metadata with each response:
            ->header('X-MC-Region', "{$region->x}, {$region->z}")
            ->header('X-MC-Chunks', "{$edge}x{$edge} from [{$offsetInRegion->x}, {$offsetInRegion->z}]")
            ->header('X-MC-Data', $chunks->count())
            ->header('X-MC-Last-Modified', $lastModified)
            ->header('X-MC-Timing', sprintf('Load: %f / Compute: %f / Render: %f', $timeLoad, $timeCompute, $timeRender))
        ;
    }
}
