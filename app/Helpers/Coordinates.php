<?php

namespace App\Helpers;

use App\Models\Chunk;
use App\Models\Region;

/**
 * Utilities for converting world coordinates to other app units.
 * 
 * @todo Stop returning models and deal only in coordinate pairs.
 * @todo Instantiate models from an abstract coordinate pair.
 */
class Coordinates
{
    public static function tileToRegion(int $zoom, int $x, int $y): Region
    {
        return new Region(
            floor($x / pow(2, $zoom)),
            floor($y / pow(2, $zoom)),
        );
    }

    /**
     * Calculates which region a chunk is in, given its X and Z location in the world.
     */
    public static function chunkToRegion(int $x, int $z): Region
    {
        return new Region(
            $x >> 5,
            $z >> 5,
        );
    }

    /**
     * Calculates which region a block is in, given its X and Z location in the world.
     */
    public static function blockToRegion(int $x, int $z): Region
    {
        return new Region(
            $x >> 9, // ?
            $z >> 9, // ?
        );
    }

    /**
     * Returns which chunk a block belongs to.
     */
    public static function blockToChunk(int $x, int $z): Chunk
    {
        $region = static::blockToRegion($x, $z);

        return $region->getChunk(
            $x >> 4,
            $z >> 4,
        );
    }

    public static function localChunkOffset(int $global): int
    {
        return $global - Region::CHUNK_DIMENSIONS * ($global >> 5);
    }

    /**
     * Returns the width of a region in "map tiles" at the given zoom level.
     */
    public static function tilesPerRegion(int $zoom): int
    {
        return pow(2, $zoom);
    }

    /**
     * Returns the width of a map tile in "chunks" at the given zoom level.
     */
    public static function chunksPerTile(int $zoom): int
    {
        return Region::CHUNK_DIMENSIONS >> $zoom;
    }
}
