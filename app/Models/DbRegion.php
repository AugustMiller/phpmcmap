<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DbRegion extends Model
{
    use HasFactory;

    /**
     * @inheritdoc
     */
    protected $table = 'regions';

    /**
     * @inheritdoc
     */
    protected $casts = [
        'last_modified' => 'datetime',
    ];

    /**
     * @inheritdoc
     */
    public $fillable = ['x', 'z'];

    public function refreshFrom(Region $region)
    {
        $chunks = $region->getChunksFrom(0, 0, Region::CHUNK_DIMENSIONS, Region::CHUNK_DIMENSIONS);

        foreach ($chunks as $chunk) {
            // Skip empty chunks:
            if ($chunk->getDataLength() === 0) {
                continue;
            }

            /** @var Chunk $chunk */
            $mod = $chunk->getLastModified();
            $dbChunk = DbChunk::firstOrCreate([
                'region_id' => $this->id,
                'x' => $chunk->x,
                'z' => $chunk->z,
            ]);

            $needsUpdate = $dbChunk->last_modified === null || $mod->gt($dbChunk->last_modified);

            if (!$needsUpdate) {
                // Nothing more to do, here!
                continue;
            }

            // Update the heightmaps:
            $dbChunk->heightmap_motion_blocking = $chunk->expandHeightmap(Chunk::NBT_TAG_HEIGHTMAP_MOTION_BLOCKING)->all();
            $dbChunk->heightmap_ocean_floor = $chunk->expandHeightmap(Chunk::NBT_TAG_HEIGHTMAP_OCEAN_FLOOR)->all();

            $dbChunk->last_modified = $mod;
            $dbChunk->save();
        }
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DbChunk::class, 'region_id');
    }

    public function chunksFrom(int $x, int $z, int $dx, int $dz): HasMany
    {
        return $this->chunks()
            ->where([
                ['x', '>=', $x],
                ['x', '<=', $x + $dx],
                ['z', '>=', $z],
                ['z', '<=', $z + $dz],
            ]);
    }
}
