<?php

namespace App\Helpers;

class Math
{
    public static function modPositive(int $num, int $mod)
    {
        return (($num % $mod) + $mod) % $mod;
    }

    public static function scale(
        int|float $num,
        int|float $inMin,
        int|float $inMax,
        int|float $outMin,
        int|float $outMax,
    )
    {
        return ($num - $inMin) * ($outMax - $outMin) / ($inMax - $inMin) + $outMin;
    }

    /**
     * Takes a range of bits from an incoming integer.
     */
    public static function sliceLong(int $long, int $start, int $length): int
    {
        // Shift down to the starting bit, then AND with as many 1s as represent the length:
        return ($long >> $start) & (pow(2, $length) - 1);
    }
}
