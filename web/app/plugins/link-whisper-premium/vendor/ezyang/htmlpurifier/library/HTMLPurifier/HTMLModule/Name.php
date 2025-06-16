<?php

namespace LWVendor;

class HTMLPurifier_HTMLModule_Name extends HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'Name';
    /**
     * @param HTMLPurifier_Config $config
     */
    public function setup($config)
    {
        $elements = array('a', 'applet', 'form', 'frame', 'iframe', 'img', 'map');
        foreach ($elements as $name) {
            $element = $this->addBlankElement($name);
            $element->attr['name'] = 'CDATA';
            if (!$config->get('HTML.Attr.Name.UseCDATA')) {
                $element->attr_transform_post[] = new HTMLPurifier_AttrTransform_NameSync();
            }
        }
    }
}
\class_alias('LWVendor\\HTMLPurifier_HTMLModule_Name', 'HTMLPurifier_HTMLModule_Name', \false);
// vim: et sw=4 sts=4
