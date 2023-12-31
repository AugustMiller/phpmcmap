<?php

namespace App\Exceptions;

use App\Models\Region;
use Exception;

class RegionDataMissingException extends Exception
{
    public function __construct(Region $region)
    {
        parent::__construct("Region data file “{$region->fileName()}” could not be found.");
    }
}
