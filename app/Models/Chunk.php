<?php

namespace App\Models;

use App\Helpers\Math;
use Aternos\Nbt\IO\Reader\ZLibCompressedStringReader;
use Aternos\Nbt\NbtFormat;
use Aternos\Nbt\Tag\CompoundTag;
use Aternos\Nbt\Tag\LongArrayTag;
use Aternos\Nbt\Tag\Tag;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Model representing a single chunk in the world.
 * 
 * @property int $x The region-relative X coordinate (East/West) of the chunk.
 * @property int $z The region-relative Z coordinate (North/South) of the chunk.
 */
class Chunk
{
    const SECTION_WORLD_FLOOR = 0;
    const SECTION_WORLD_CEIL = 15;
    const BLOCK_DIMENSIONS = 16;

    const NBT_KEY_HEIGHTMAP = 'Heightmaps';

    const NBT_TAG_HEIGHTMAP_MOTION_BLOCKING = 'MOTION_BLOCKING';
    const NBT_TAG_HEIGHTMAP_MOTION_BLOCKING_NO_LEAVES = 'MOTION_BLOCKING_NO_LEAVES';
    const NBT_TAG_HEIGHTMAP_OCEAN_FLOOR = 'OCEAN_FLOOR';
    const NBT_TAG_HEIGHTMAP_OCEAN_FLOOR_WG = 'OCEAN_FLOOR_WG';
    const NBT_TAG_HEIGHTMAP_WORLD_SURFACE = 'WORLD_SURFACE';
    const NBT_TAG_HEIGHTMAP_WORLD_SURFACE_WG = 'WORLD_SURFACE_WG';

    private ZLibCompressedStringReader|null|false $data = null;
    private ?Tag $nbt = null;
    private ?Collection $sections = null;

    public function __construct(
        public Region $region,
        public int $x,
        public int $z,
    )
    {}

    /**
     * Returns the offset for the chunk's data location in the header table.
     */
    public function getLocationOffset(): int
    {
        return Region::LOOKUP_CELL_LENGTH * (Math::modPositive($this->x, Region::CHUNK_DIMENSIONS) + Math::modPositive($this->z, Region::CHUNK_DIMENSIONS) * Region::CHUNK_DIMENSIONS);
    }

    /**
     * Like {@see getLocationOffset()}, returns a lookup table location for the chunk’s last modified time. The actual time is available via {@see getLastModified()}.
     */
    public function getTimestampOffset(): int
    {
        // These are always just 4KiB later:
        return $this->getLocationOffset() + (Region::HEADER_LENGTH / 2);
    }

    public function getDataOffset(): int
    {
        $data = substr($this->region->getChunkLocations(), $this->getLocationOffset(), Region::LOOKUP_CELL_LENGTH);

        // Unsigned long, 32 bit, big endian:
        $padded = "\x00" . substr($data, 0, 3);
        $offset = unpack('Nloc', $padded)['loc'];

        return $offset * Region::CHUNK_SECTOR_LENGTH;
    }

    public function getDataLength(): int
    {
        $data = substr($this->region->getChunkLocations(), $this->getLocationOffset(), Region::LOOKUP_CELL_LENGTH);

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

    public function getLastModified(): Carbon
    {
        $data = substr($this->region->getHeaders(), $this->getTimestampOffset(), Region::LOOKUP_CELL_LENGTH);

        // Unsigned long, 32 bit, big endian:
        $timestamp = unpack('Nts', $data)['ts'];

        return Carbon::createFromTimestamp($timestamp);
    }

    /**
     * Returns an NBT reader instance for the compressed chunk data.
     * 
     * The raw chunk data begins with a header that describes the exact length of the compressed data; the length listed in the region’s header only contains the number of 4KB sectors, but the actual length will sometimes differ (i.e. due to padding):
     * 
     * [ L L L L F D D D D D D D ... ]
     */
    public function getData(): ZLibCompressedStringReader|null|false
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $offset = $this->getDataOffset();
        $length = $this->getDataLength();

        if ($length === 0) {
            return null;
        }

        // Get raw data from region blob:
        $raw = substr($this->region->getData(), $offset, $length);

        // Find data length from header:
        $nbtLength = unpack('Nlen', substr($raw, 0, 4))['len'];

        // Get the compression format:
        $format = unpack('Nformat', "\x00\x00\x00" . substr($raw, 4, 1))['format'];

        // Carve out compressed NBT data (stepping over the compression type at byte 4):
        $data = substr($raw, 5, $nbtLength);

        if (strlen($data) === 0) {
            return $this->data = false;
        }

        /** @todo Refactor this to use [[Data::parseNbt()]]! */
        try {
            $dataReader = new ZLibCompressedStringReader($data, NbtFormat::JAVA_EDITION);
        } catch (Exception $e) {
            Log::error("Failed to decompress data for chunk [{$this->x}, {$this->z}]: {$e->getMessage()}");

            return $this->data = false;
        }

        return $this->data = $dataReader;
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
    {}

    public function getHighestPopulatedSection(): ?Section
    {
        $sections = $this->getWorldSections();

        $sections->reverse();
        return $sections->reverse()->firstWhere(function($s) {
            return !$s->isEmpty();
        });
    }

    public function getHeightmap(string $name): ?LongArrayTag
    {
        return $this->getNbtData()
            ->getCompound(self::NBT_KEY_HEIGHTMAP)
            ->getLongArray($name);
    }

    public function expandHeightmap(string $name): Collection
    {
        $heightmap = Collection::make($this->getHeightmap($name));

        return $heightmap->
            map(function($l) {
                $blocks = [];

                for ($i = 0; $i < 7; $i++) {
                    $blocks[] = Math::sliceLong($l, $i * 9, 9);
                }

                return $blocks;
            })
            // Each long is split, so we're left with an array-of-arrays:
            ->flatten()
            // We only want the 16x16 grid, so trim it down in case there is a straggler from unpacking the longs:
            ->slice(0, pow(self::BLOCK_DIMENSIONS, 2));
    }

    private function getCacheKey(string $ns = 'data'): string
    {
        return join('_', [
            'r',
            $this->region->x,
            $this->region->z,
            'c',
            $this->x,
            $this->z,
            $ns,
        ]);
    }
}
