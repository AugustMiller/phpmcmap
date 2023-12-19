<?php

namespace App\Models;

use Aternos\Nbt\Tag\CompoundTag;

class Section
{
    const NBT_KEY_BLOCK_STATES = 'block_states';
    const NBT_KEY_PALETTE = 'palette';
    const NBT_KEY_DATA = 'data';
    const NBT_KEY_Y = 'Y';

    public ?int $y = null;

    public function __construct(
        public CompoundTag $data,
        public Chunk $chunk,
    )
    {
        $this->y = $this->data->getByte(self::NBT_KEY_Y)->getValue();
        if (!$this->isEmpty()) {
        }
    }

    public function isEmpty(): bool
    {
        if ($this->data === null) {
            return true;
        }

        return $this->data->get(self::NBT_KEY_BLOCK_STATES)->get(self::NBT_KEY_DATA) === null;
    }
}
