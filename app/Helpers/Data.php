<?php

namespace App\Helpers;

use Aternos\Nbt\IO\Reader\ZLibCompressedStringReader;
use Aternos\Nbt\NbtFormat;
use Aternos\Nbt\Tag\Tag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Traversable;

/**
 * NBT data functions
 */
class Data
{
    public static function parseNbt(string $data): ?Tag
    {
        try {
            $reader = new ZLibCompressedStringReader($data, NbtFormat::JAVA_EDITION);
        } catch (\Exception $e) {
            Log::error("Failed to decompress NBT data: {$e->getMessage()}");

            return null;
        }

        return Tag::load($reader);
    }

    public static function getPlayerNameByUuid(string $uuid): ?string
    {
        $cacheKey = "playername:{$uuid}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::get("https://sessionserver.mojang.com/session/minecraft/profile/{$uuid}");
        $data = $response->json();

        $name = $data['name'] ?? null;

        // Set it in the cache, even if it’s `null`:
        Cache::set($cacheKey, $name);

        return $name;
    }

    public static function convertIntArrayToUuid(Traversable $ints): string
    {
        $uuid = '';

        foreach ($ints as $int) {
            if ($int < 0) {
                $int += 0x100000000;
            }

            # Add line to a string, must be 8 digits, so fill it up with "0" on the left side
            $uuid .= str_pad((string)dechex($int), 8, '0', STR_PAD_LEFT);
        }

        // Remove zeroes again?
        $uuid = substr_replace($uuid, '-', 8, 0);
        $uuid = substr_replace($uuid, '-', 13, 0);
        $uuid = substr_replace($uuid, '-', 18, 0);
        $uuid = substr_replace($uuid, '-', 23, 0);

        return $uuid;
    }
}
