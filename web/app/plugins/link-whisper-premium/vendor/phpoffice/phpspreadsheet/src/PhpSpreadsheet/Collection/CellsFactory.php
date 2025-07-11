<?php

namespace LWVendor\PhpOffice\PhpSpreadsheet\Collection;

use LWVendor\PhpOffice\PhpSpreadsheet\Settings;
use LWVendor\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
abstract class CellsFactory
{
    /**
     * Initialise the cache storage.
     *
     * @param Worksheet $worksheet Enable cell caching for this worksheet
     *
     * */
    public static function getInstance(Worksheet $worksheet) : Cells
    {
        return new Cells($worksheet, Settings::getCache());
    }
}
