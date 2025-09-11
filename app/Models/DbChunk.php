<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $x
 * @property int $z
 * @property int $region_id
 */
class DbChunk extends Model
{
    use HasFactory;

    /**
     * @inheritdoc
     */
    protected $table = 'chunks';

    /**
     * @inheritdoc
     */
    public $fillable = ['x', 'z', 'region_id'];

    /**
     * @inheritdoc
     */
    protected $casts = [
        'last_modified' => 'datetime',
    ];

    /**
     * @inheritdoc
     */
    protected $attributes = [
        'heightmap_motion_blocking' => '[]',
        'heightmap_ocean_floor' => '[]',
    ];

    /**
     * @inheritdoc
     */
    protected function casts(): array
    {
        return [
            'heightmap_motion_blocking' => AsCollection::class,
            'heightmap_ocean_floor' => AsCollection::class,
        ];
    }

    /**
     * Gets the parent DbRegion.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(DbRegion::class, null, 'region_id');
    }

    /**
     * Loads points-of-interest present within the chunk.
     */
    public function poi(): HasMany
    {
        return $this->hasMany(DbPoi::class, 'chunk_id');
    }

    /**
     * Returns whether the heightmaps are populated.
     */
    public function hasHeightmaps(): bool
    {
        return !$this->heightmap_motion_blocking->isEmpty() && !$this->heightmap_ocean_floor->isEmpty();
    }

    /**
     * Populates downstream chunk data from the provided chunk NBT wrapper.
     */
    public function refreshFrom(Chunk $chunk): void
    {
        // Update the heightmaps:
        $heightmapMotionBlocking = $chunk->expandHeightmap(Chunk::NBT_TAG_HEIGHTMAP_MOTION_BLOCKING);
        $heightmapOceanFloor = $chunk->expandHeightmap(Chunk::NBT_TAG_HEIGHTMAP_OCEAN_FLOOR);

        // Memoize averages to save time at lower zoom levels:
        $this->average_height_motion_blocking = round($heightmapMotionBlocking->average());
        $this->average_height_ocean_floor = round($heightmapOceanFloor->average());

        $this->heightmap_motion_blocking = $heightmapMotionBlocking->all();
        $this->heightmap_ocean_floor = $heightmapOceanFloor->all();

        // Stash the chunk’s modified timestamp so we can compare later:
        $this->last_modified = $chunk->getLastModified();
        $this->save();

        // Clear POI:
        $this->poi()->delete();

        $entities = [];

        foreach ($chunk->getBlockEntities() as $entity) {
            /** @var \Aternos\Nbt\Tag\CompoundTag $entity */
            $entities[] = [
                'x' => $entity->getInt('x')->getValue(),
                'z' => $entity->getInt('z')->getValue(),
                'y' => $entity->getInt('y')->getValue(),
                'entity_type' => $entity->getString('id')->getValue(),
                'metadata' => $entity,
            ];
        }

        $this->poi()->createMany($entities);
    }
}
