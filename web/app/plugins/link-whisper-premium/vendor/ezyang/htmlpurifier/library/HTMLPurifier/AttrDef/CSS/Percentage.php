<?php

namespace LWVendor;

/**
 * Validates a Percentage as defined by the CSS spec.
 */
class HTMLPurifier_AttrDef_CSS_Percentage extends HTMLPurifier_AttrDef
{
    /**
     * Instance to defer number validation to.
     * @type HTMLPurifier_AttrDef_CSS_Number
     */
    protected $number_def;
    /**
     * @param bool $non_negative Whether to forbid negative values
     */
    public function __construct($non_negative = \false)
    {
        $this->number_def = new HTMLPurifier_AttrDef_CSS_Number($non_negative);
    }
    /**
     * @param string $string
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($string, $config, $context)
    {
        $string = $this->parseCDATA($string);
        if ($string === '') {
            return \false;
        }
        $length = \strlen($string);
        if ($length === 1) {
            return \false;
        }
        if ($string[$length - 1] !== '%') {
            return \false;
        }
        $number = \substr($string, 0, $length - 1);
        $number = $this->number_def->validate($number, $config, $context);
        if ($number === \false) {
            return \false;
        }
        return "{$number}%";
    }
}
/**
 * Validates a Percentage as defined by the CSS spec.
 */
\class_alias('LWVendor\\HTMLPurifier_AttrDef_CSS_Percentage', 'HTMLPurifier_AttrDef_CSS_Percentage', \false);
// vim: et sw=4 sts=4
