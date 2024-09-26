<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DbPoi extends Model
{
    use HasFactory;

    /**
     * @inheritdoc
     */
    protected $table = 'poi';

    /**
     * @inheritdoc
     */
    public $fillable = ['x', 'z', 'entity_type', 'label', 'metadata'];

    /**
     * @inheritdoc
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
    /**
     * The chunk this POI was found in.
     */
    public function chunk(): BelongsTo
    {
        return $this->belongsTo(DbChunk::class, null, 'chunk_id');
    }
}
