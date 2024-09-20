<?php

namespace App\Helpers;

/**
 * Color management utilities.
 */
class Color
{
    public const MIN = 0;
    public const MAX = 255;

    public function __construct(
        public int $r = 0,
        public int $g = 0,
        public int $b = 0,
    )
    {}

    public function __toString()
    {
        return "rgb({$this->r}, {$this->g}, {$this->b})";
    }

    public function round(): self
    {
        $this->r = round($this->r);
        $this->g = round($this->g);
        $this->b = round($this->b);

        return $this;
    }

    public static function colorFromElevation(int $height): array
    {
        return new static(255, 255, 255);
    }
}
