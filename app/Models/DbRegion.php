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
    public $fillable = ['x', 'z'];

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
     * Chunks belonging to this region. Maximum 1024 (32 * 32).
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(DbChunk::class, 'region_id');
    }

    /**
     * Loads chunks in a rectangle with the given width $dx and height $dz, starting at point $x, $z.
     * 
     * You may apply additional constraints on the returned query.
     */
    public function chunksFrom(int $x, int $z, int $dx, int $dz): HasMany
    {
        return $this->chunks()
            ->where([
                ['x', '>=', $x],
                ['x', '<', $x + $dx],
                ['z', '>=', $z],
                ['z', '<', $z + $dz],
            ])
            ->orderBy('z')
            ->orderBy('x');
    }

    /**
     * Populates the database from the given region file model.
     */
    public function refreshFrom(Region $region, bool $forceUpdate = false)
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

            $needsUpdate = $forceUpdate || $dbChunk->last_modified === null || $mod->gt($dbChunk->last_modified);

            if (!$needsUpdate) {
                // Nothing more to do, here!
                continue;
            }

            $dbChunk->refreshFrom($chunk);
        }
    }
}
