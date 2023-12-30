<?php

namespace App\Models;

class Vector
{
    public function __construct(
        public float $x = 0.0,
        public float $z = 0.0,
        public float $y = 0.0,
    )
    {}

    public function absX(): float
    {
        return abs($this->x);
    }

    public function absZ(): float
    {
        return abs($this->z);
    }

    public function absY(): float
    {
        return abs($this->y);
    }

    public function scale(int|float $dx, int|float $dz, int|float $dy): static
    {
        return new Vector(
            $dx * $this->x,
            $dz * $this->z,
            $dy * $this->y,
        );
    }
}
