<?php

namespace LWVendor\PhpOffice\PhpSpreadsheet\Writer;

use LWVendor\ZipStream\Option\Archive;
use LWVendor\ZipStream\ZipStream;
class ZipStream0
{
    /**
     * @param resource $fileHandle
     */
    public static function newZipStream($fileHandle) : ZipStream
    {
        return \class_exists(Archive::class) ? ZipStream2::newZipStream($fileHandle) : ZipStream3::newZipStream($fileHandle);
    }
}
