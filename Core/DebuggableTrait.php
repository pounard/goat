<?php

namespace Goat\Core;

use Goat\Core\Error\GoatError;

trait DebuggableTrait /* implements DebuggableInterface */
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

    public function debugMessage($message, $level = E_USER_WARNING)
    {
        if ($this->debug) {
            trigger_error($message, $level);
        }
    }

    public function debugRaiseException($message = null, $code = null, $previous = null)
    {
        if ($this->debug) {
            throw new GoatError($message, $code, $previous);
        }
    }
}
