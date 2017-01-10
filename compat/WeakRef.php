<?php
/**
 * WeakRef PHP extension polyfill.
 *
 * https://secure.php.net/manual/en/class.weakref.php
 */

class WeakRef
{
    private $object;

    public function __construct($object)
    {
        $this->object = $object;
    }

    public /* bool */ function acquire()
    {
        // Nothing to be done, we cannot support weak reference.
    }

    public /* object */ function get()
    {
        return $this->object;
    }

    public /* bool */ function release()
    {
        // Nothing to be done, we cannot support weak reference.
    }

    public /* bool */ function valid()
    {
        return true;
    }
}
