<?php

namespace Goat\Core;

interface DebuggableInterface
{
    public function isDebugEnabled();

    public function setDebug($debug = true);

    /**
     * Send error if debug mode is enabled
     *
     * @param string $message
     * @param string $level
     */
    public function debugMessage($message, $level = E_USER_WARNING);

    /**
     * Send exception if debug mode is enabled
     *
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     */
    public function debugRaiseException($message = null, $code = null, $previous = null);
}
