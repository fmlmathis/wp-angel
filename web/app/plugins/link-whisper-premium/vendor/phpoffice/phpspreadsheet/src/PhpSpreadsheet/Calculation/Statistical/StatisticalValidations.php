<?php

namespace LWVendor\PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use LWVendor\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use LWVendor\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
class StatisticalValidations
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
    /**
     * @param mixed $value
     */
    public static function validateBool($value) : bool
    {
        if (!\is_bool($value) && !\is_numeric($value)) {
            throw new Exception(ExcelError::VALUE());
        }
        return (bool) $value;
    }
}
