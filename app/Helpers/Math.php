<?php

namespace App\Helpers;

class Math
{
    public static function modPositive(int $num, int $mod)
    {
        return (($num % $mod) + $mod) % $mod;
    }
}
