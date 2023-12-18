<?php

namespace App\Models;

use App\Exceptions\RegionDataMissingException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\String\ByteString;

class Region
{
    const HEADER_LENGTH = 8192;
    const CHUNK_SECTOR_LENGTH = 4096;
    const LOOKUP_CELL_LENGTH = 4;
    const CHUNK_DIMENSIONS = 32;

    public string $headers;
    public string $locations;
    public string $timestamps;
    public string $chunks;

    private ?string $data = null;

    public function __construct(
        public int $x,
        public int $z,
    )
    {
        if (!$this->fileExists()) {
            throw new RegionDataMissingException();
        }

        // Store all headers in one blob:
        $this->headers = substr($this->getData(), 0, self::HEADER_LENGTH);

        // Store location + date tables separately:
        $this->locations = substr($this->headers, 0, self::HEADER_LENGTH / 2);
        $this->timestamps = substr($this->headers, self::HEADER_LENGTH / 2, self::HEADER_LENGTH / 2);

        // The remainder of the data goes into `chunks`:
        $this->chunks = substr($this->getData(), self::HEADER_LENGTH);
    }

    public function fileExists(): bool
    {
        return file_exists($this->filePath());
    }

    public function filePath(): string
    {
        return resource_path("data/{$this->fileName()}");
    }

    public function fileName(): string
    {
        return "r.{$this->x}.{$this->z}.mca";
    }

    public function getData(): string
    {
        if ($this->data !== null) {
            return $this->data;
        }

        return $this->data = file_get_contents($this->filePath());
    }

    public function getChunk(int $x, int $z): Chunk
    {
        return new Chunk($this, $x, $z);
    }

    public function getChunksFrom(int $x, int $z, int $width, int $height): Collection
    {
        $chunks = Collection::make();

        for ($w = 0; $w < $width; $w++) {
            for ($h = 0; $h < $height; $h++) {
                $chunks->push($this->getChunk($x + $w, $z + $h));
            }
        }

        return $chunks;
    }
}
