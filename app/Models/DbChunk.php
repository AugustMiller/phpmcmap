<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
