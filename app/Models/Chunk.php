<?php

namespace App\Models;

use DateTime;
use Aternos\Nbt\IO\Reader\ZLibCompressedStringReader;
use Aternos\Nbt\NbtFormat;
use Aternos\Nbt\Tag\CompoundTag;
use Aternos\Nbt\Tag\Tag;
use Exception;

class Chunk
{
    public $region;
    public $x;
    public $z;

    public function __construct(Region $region, int $x, int $z)
    {
        $this->region = $region;
        $this->x = $x;
        $this->z = $z;
    }

    public function getDataOffset(): int
    {
        $data = substr($this->region->locations, $this->getLocationOffset(), Region::LOOKUP_CELL_LENGTH);

        // Unsigned long, 32 bit, big endian:
        $padded = "\x00" . substr($data, 0, 3);
        $offset = unpack('Nloc', $padded)['loc'];

        return $offset * Region::CHUNK_SECTOR_LENGTH;
    }

    public function getDataLength(): int
    {
        $data = substr($this->region->locations, $this->getLocationOffset(), Region::LOOKUP_CELL_LENGTH);

        // Unsigned long, 32 bit, big endian:
        $padded = "\x00\x00\x00" . substr($data, 3, 1);

        try {
            $sectors = unpack('Nlen', $padded)['len'];
        } catch (Exception $e) {
            // Invalid table data? Probably no data:
            return 0;
        }

        return $sectors * Region::CHUNK_SECTOR_LENGTH;
    }

    public function getLocationOffset(): int
    {
        return Region::LOOKUP_CELL_LENGTH * (($this->x % Region::CHUNK_DIMENSIONS) + ($this->z % Region::CHUNK_DIMENSIONS) * Region::CHUNK_DIMENSIONS);
    }

    public function getTimestampOffset(): int
    {
        // These are always just 4KiB later:
        return $this->getLocationOffset() + (Region::HEADER_LENGTH / 2);
    }

    public function getLastModified(): DateTime
    {
        $data = substr($this->region->headers, $this->getTimestampOffset(), Region::LOOKUP_CELL_LENGTH);

        // Unsigned long, 32 bit, big endian:
        $timestamp = unpack('Nts', $data)['ts'];

        return new DateTime("@{$timestamp}");
    }

    public function getData(): ZLibCompressedStringReader
    {
        $offset = $this->getDataOffset();
        $length = $this->getDataLength();

        // Get raw data from region blob:
        $raw = substr($this->region->chunks, $offset, $length);

        // Find length from header:
        $length = unpack('Nlen', substr($raw, 0, 4))['len'];

        // Carve out compressed NBT data (skipping the compression type at byte 4):
        $data = substr($raw, 5, $length);

        return new ZLibCompressedStringReader($data, NbtFormat::JAVA_EDITION);
    }

    public function getNbtData(): CompoundTag
    {
        return Tag::load($this->getData());
    }

    public function getHighestBlockAt(int $x, int $z)
    {

    }

    public function getHighestSectorWithBlocks(): int
    {
        
    }
}
