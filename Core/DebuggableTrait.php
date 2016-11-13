<?php

namespace Goat\Core;

trait DebuggableTrait
{
    protected $debug = false;

    public function isDebugEnabled()
    {
        return $this->debug;
    }

    public function setDebug($debug = true)
    {
        $this->debug = (bool)$debug;
    }
}
