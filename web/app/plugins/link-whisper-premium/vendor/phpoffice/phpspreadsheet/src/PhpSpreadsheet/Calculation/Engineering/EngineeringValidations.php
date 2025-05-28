<?php

namespace LWVendor\PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use LWVendor\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use LWVendor\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
class EngineeringValidations
{
    /**
     * @param mixed $value
     */
    public static function validateFloat($value) : float
    {
        if (!\is_numeric($value)) {
            throw new Exception(ExcelError::VALUE());
        }
        return (float) $value;
    }
    /**
     * @param mixed $value
     */
    public static function validateInt($value) : int
    {
        if (!\is_numeric($value)) {
            throw new Exception(ExcelError::VALUE());
        }
        return (int) \floor((float) $value);
    }
}
