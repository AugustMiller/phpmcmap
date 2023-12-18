<?php

namespace App\Models;

use Imagick;
use ImagickDraw;
use ImagickPixel;

class Tile
{
    public const HEIGHT = 256;
    public const WIDTH = 256;

    public string $label;

    private Imagick $image;

    public function __construct(string $label = 'Tile')
    {
        $this->label = $label;

        $this->image = new Imagick();
        $this->image->newImage(self::WIDTH, self::HEIGHT, new ImagickPixel('transparent'));
        $this->image->setImageFormat('png');

        $drawing = new ImagickDraw();

        $drawing->setStrokeColor(new ImagickPixel('black'));
        $drawing->setFillColor(new ImagickPixel('transparent'));
        $drawing->rectangle(0, 0, self::WIDTH - 1, self::HEIGHT - 1);

        $this->image->drawImage($drawing);
    }

    public function getImage(): Imagick
    {
        return $this->image;
    }

    public function writeMetadata(): void
    {
        $drawing = new ImagickDraw();

        $drawing->annotation(15, 25, $this->label);

        $this->image->drawImage($drawing);
    }

    public function draw(callable $fn): void
    {
        $this->image->drawImage($fn(new ImagickDraw()));
    }
}
