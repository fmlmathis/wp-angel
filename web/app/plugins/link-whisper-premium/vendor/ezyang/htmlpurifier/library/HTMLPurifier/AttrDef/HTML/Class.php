<?php

namespace LWVendor;

/**
 * Implements special behavior for class attribute (normally NMTOKENS)
 */
class HTMLPurifier_AttrDef_HTML_Class extends HTMLPurifier_AttrDef_HTML_Nmtokens
{
    /**
     * @param string $string
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return bool|string
     */
    protected function split($string, $config, $context)
    {
        // really, this twiddle should be lazy loaded
        $name = $config->getDefinition('HTML')->doctype->name;
        if ($name == "XHTML 1.1" || $name == "XHTML 2.0") {
            return parent::split($string, $config, $context);
        } else {
            return \preg_split('/\\s+/', $string);
        }
    }
    /**
     * @param array $tokens
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return array
     */
    protected function filter($tokens, $config, $context)
    {
        $allowed = $config->get('Attr.AllowedClasses');
        $forbidden = $config->get('Attr.ForbiddenClasses');
        $ret = array();
        foreach ($tokens as $token) {
            if (($allowed === null || isset($allowed[$token])) && !isset($forbidden[$token]) && !\in_array($token, $ret, \true)) {
                $ret[] = $token;
            }
        }
        return $ret;
    }
}
/**
 * Implements special behavior for class attribute (normally NMTOKENS)
 */
\class_alias('LWVendor\\HTMLPurifier_AttrDef_HTML_Class', 'HTMLPurifier_AttrDef_HTML_Class', \false);
