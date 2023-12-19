<?php

namespace App\Models;

use DateTime;
use Aternos\Nbt\IO\Reader\ZLibCompressedStringReader;
use Aternos\Nbt\NbtFormat;
use Aternos\Nbt\Tag\CompoundTag;
use Aternos\Nbt\Tag\Tag;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Chunk
{
    const SECTION_WORLD_FLOOR = 0;
    const SECTION_WORLD_CEIL = 15;

    private ZLibCompressedStringReader|null|false $data = null;
    private ?Tag $nbt = null;
    private ?Collection $sections = null;

    public function __construct(
        public Region $region,
        public int $x,
        public int $z,
    )
    {}

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
            Log::error("Failed to unpack data for Chunk [{$this->x}, {$this->z}]: {$e->getMessage()}");

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

    public function getData(): ZLibCompressedStringReader|null|false
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $offset = $this->getDataOffset();
        $length = $this->getDataLength();

        // Get raw data from region blob:
        $raw = substr($this->region->chunks, $offset, $length);

        // Find length from header:
        $length = unpack('Nlen', substr($raw, 0, 4))['len'];

        // Get the compression format:
        $format = unpack('Nformat', "\x00\x00\x00" . substr($raw, 4, 1))['format'];

        // Carve out compressed NBT data (skipping the compression type at byte 4):
        $data = substr($raw, 5, $length);

        if (strlen($data) === 0) {
            return $this->data = false;
        }

        try {
            $decompressed = new ZLibCompressedStringReader($data, NbtFormat::JAVA_EDITION);
        } catch (Exception $e) {
            Log::error("Failed to decompress data for chunk [{$this->x}, {$this->z}]: {$e->getMessage()}");

            return $this->data = false;
        }

        return $this->data = $decompressed;
    }

    public function getNbtData(): ?CompoundTag
    {
        if ($this->nbt !== null) {
            return $this->nbt;
        }

        $data = $this->getData();

        if (empty($data)) {
            return $this->nbt = null;
        }

        try {
            $nbt = Tag::load($this->getData());
        } catch (Exception $e) {
            Log::error("Failed to load NBT data for Chunk [{$this->x}, {$this->z}]: {$e->getMessage()}");

            return $this->nbt = null;
        }

        return $this->nbt = $nbt;
    }

    public function getSections(): Collection
    {
        if ($this->sections !== null) {
            return $this->sections;
        }

        $data = $this->getNbtData();

        if ($data === null) {
            // Empty collection:
            return Collection::make();
        }

        $sections = $data->get('sections');

        return $this->sections = Collection::make($sections)
            ->map(function($s) {
                return new Section($s, $this);
            });
    }

    public function getWorldSections(): Collection
    {
        return $this->getSections()
            ->where(function($s) {
                return $s->y >= self::SECTION_WORLD_FLOOR && $s->y <= self::SECTION_WORLD_CEIL;
            });
    }

    public function getSection(int $y): CompoundTag
    {
        return $this->getSections()->get($y);
    }

    public function getBlock(int $x, int $z, int $y): Block
    {
        return new Block($x, $z, $y, $this);
    }

    public function getHighestBlockAt(int $x, int $z)
    {

    }

    public function getHighestPopulatedSection(): ?Section
    {
        $sections = $this->getWorldSections();

        $sections->reverse();
        return $sections->reverse()->firstWhere(function($s) {
            return !$s->isEmpty();
        });
    }
}
