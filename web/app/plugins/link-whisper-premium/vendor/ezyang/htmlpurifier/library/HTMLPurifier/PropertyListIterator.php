<?php

namespace LWVendor;

/**
 * Property list iterator. Do not instantiate this class directly.
 */
class HTMLPurifier_PropertyListIterator extends \FilterIterator
{
    /**
     * @type int
     */
    protected $l;
    /**
     * @type string
     */
    protected $filter;
    /**
     * @param Iterator $iterator Array of data to iterate over
     * @param string $filter Optional prefix to only allow values of
     */
    public function __construct(\Iterator $iterator, $filter = null)
    {
        parent::__construct($iterator);
        $this->l = \strlen($filter);
        $this->filter = $filter;
    }
    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function accept()
    {
        $key = $this->getInnerIterator()->key();
        if (\strncmp($key, $this->filter, $this->l) !== 0) {
            return \false;
        }
        return \true;
    }
}
/**
 * Property list iterator. Do not instantiate this class directly.
 */
\class_alias('LWVendor\\HTMLPurifier_PropertyListIterator', 'HTMLPurifier_PropertyListIterator', \false);
// vim: et sw=4 sts=4
