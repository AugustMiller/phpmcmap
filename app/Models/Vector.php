<?php

namespace App\Models;

class Vector
{
    public function __construct(
        public float $x,
        public float $y,
    )
    {}

    public function absX(): float
    {
        return abs($this->x);
    }

    public function absY(): float
    {
        return abs($this->y);
    }

    public function scale(int|float $dx, int|float $dy): static
    {
        return new Vector($dx * $this->x, $dy * $this->y);
    }
}
