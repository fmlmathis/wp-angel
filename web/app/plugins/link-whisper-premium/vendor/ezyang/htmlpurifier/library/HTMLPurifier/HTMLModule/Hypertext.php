<?php

namespace LWVendor;

/**
 * XHTML 1.1 Hypertext Module, defines hypertext links. Core Module.
 */
class HTMLPurifier_HTMLModule_Hypertext extends HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'Hypertext';
    /**
     * @param HTMLPurifier_Config $config
     */
    public function setup($config)
    {
        $a = $this->addElement('a', 'Inline', 'Inline', 'Common', array(
            // 'accesskey' => 'Character',
            // 'charset' => 'Charset',
            'href' => 'URI',
            // 'hreflang' => 'LanguageCode',
            'rel' => new HTMLPurifier_AttrDef_HTML_LinkTypes('rel'),
            'rev' => new HTMLPurifier_AttrDef_HTML_LinkTypes('rev'),
        ));
        $a->formatting = \true;
        $a->excludes = array('a' => \true);
    }
}
/**
 * XHTML 1.1 Hypertext Module, defines hypertext links. Core Module.
 */
\class_alias('LWVendor\\HTMLPurifier_HTMLModule_Hypertext', 'HTMLPurifier_HTMLModule_Hypertext', \false);
// vim: et sw=4 sts=4
