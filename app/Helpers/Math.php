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
}
