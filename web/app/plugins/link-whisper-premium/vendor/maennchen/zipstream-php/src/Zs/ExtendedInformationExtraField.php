<?php

declare (strict_types=1);
namespace LWVendor\ZipStream\Zs;

use LWVendor\ZipStream\PackField;
/**
 * @internal
 */
abstract class ExtendedInformationExtraField
{
    private const TAG = 0x5653;
    public static function generate() : string
    {
        return PackField::pack(new PackField(format: 'v', value: self::TAG), new PackField(format: 'v', value: 0x0));
    }
}
