<?php

namespace App\Exceptions;

use Exception;

class RegionDataMissingException extends Exception
{
    protected $message = 'Region data file could not be found.';
}
