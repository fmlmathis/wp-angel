<?php

declare (strict_types=1);
namespace LWVendor\ZipStream\Exception;

use LWVendor\ZipStream\Exception;
/**
 * This Exception gets invoked if a stream can't be read.
 */
class StreamNotReadableException extends Exception
{
    /**
     * @internal
     */
    public function __construct()
    {
        parent::__construct('The stream could not be read.');
    }
}
