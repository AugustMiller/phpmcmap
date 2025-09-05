<?php

namespace App\Models;

use App\Exceptions\RegionDataMissingException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * @var int $x X region coordinate.
 * @var int $z Z region coordinate.
 */
class Region
{
    const HEADER_LENGTH = 8192;
    const CHUNK_SECTOR_LENGTH = 4096;
    const LOOKUP_CELL_LENGTH = 4;
    const CHUNK_DIMENSIONS = 32;

    public string $headers;
    public string $chunkLocations;
    public string $chunkTimestamps;
    public string $chunks;

    private ?string $data = null;

    public function __construct(
        public int $x,
        public int $z,
    )
    {
        if (!$this->fileExists()) {
            throw new RegionDataMissingException($this);
        }

        // Store all headers in one blob:
        $this->headers = substr($this->getData(), 0, self::HEADER_LENGTH);

        // Store location + date tables separately:
        $this->chunkLocations = substr($this->headers, 0, self::HEADER_LENGTH / 2);
        $this->chunkTimestamps = substr($this->headers, self::HEADER_LENGTH / 2, self::HEADER_LENGTH / 2);

        // The remainder of the data goes into `chunks`:
        $this->chunks = substr($this->getData(), self::HEADER_LENGTH);
    }

    public function fileExists(): bool
    {
        return Storage::disk('region')->exists($this->fileName());
    }

    public function fileName(): string
    {
        return "r.{$this->x}.{$this->z}.mca";
    }

    public function getData(): string
    {
        if ($this->data === null) {
            $this->data = Storage::disk('region')->get($this->fileName());
        }

        return $this->data;
    }

    public function getHeaders(): string
    {
        if ($this->headers === null) {
            // Store all headers in one blob:
            $this->headers = substr($this->getData(), 0, self::HEADER_LENGTH);
        }

        return $this->headers;
    }

    public function getChunkLocations(): string
    {
        if ($this->chunkLocations === null) {
            $this->chunkLocations = substr($this->headers, 0, self::HEADER_LENGTH / 2);
        }

        return $this->chunkLocations;
    }

    public function getChunkTimestamps(): string
    {
        if ($this->chunkTimestamps === null) {
            $this->chunkTimestamps = substr($this->headers, self::HEADER_LENGTH / 2, self::HEADER_LENGTH / 2);
        }

        return $this->chunkTimestamps;
    }

    public function getChunks(): string
    {
        if ($this->chunks === null) {
            $this->chunks = substr($this->getData(), self::HEADER_LENGTH);
        }

        return $this->chunks;
    }

    public function getChunk(int $x, int $z): Chunk
    {
        return new Chunk($this, $x, $z);
    }

    public function getChunksFrom(int $x, int $z, int $width, int $height, int $resolution = 1): Collection
    {
        $chunks = Collection::make();

        // Outer loop is rows; inner loop is columns:
        for ($h = 0; $h < $height; $h = $h + $resolution) {
            for ($w = 0; $w < $width; $w = $w + $resolution) {
                $chunks->push($this->getChunk($x + $w, $z + $h));
            }
        }

        return $chunks;
    }
}
