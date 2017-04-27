<?php

declare(strict_types=1);

namespace Goat\Core;

use Goat\Error\GoatError;

trait DebuggableTrait /* implements DebuggableInterface */
{
    protected $debug = false;

    public function isDebugEnabled() : bool
    {
        return $this->debug;
    }

    public function setDebug(bool $debug = true)
    {
        $this->debug = $debug;
    }

    public function debugMessage(string $message, int $level = E_USER_WARNING)
    {
        if ($this->debug) {
            trigger_error($message, $level);
        }
    }

    public function debugRaiseException(string $message = null, int $code = null, \Throwable $previous = null)
    {
        if ($this->debug) {
            throw new GoatError($message, $code, $previous);
        }
    }
}
