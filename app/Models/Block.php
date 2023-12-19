<?php

namespace App\Models;

class Block
{
    public function __construct(
        public int $x,
        public int $z,
        public int $y,
        public Chunk $chunk,
    )
    {

    }
}
