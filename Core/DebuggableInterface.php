<?php

namespace Goat\Core;

/**
 * Components that can be debugged.
 */
interface DebuggableInterface
{
    /**
     * Is debug mode enabled
     *
     * @return bool
     */
    public function isDebugEnabled() : bool;

    /**
     * Enable or disable debug mode
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug = true);

    /**
     * Send error if debug mode is enabled
     *
     * @param string $message
     * @param string $level
     */
    public function debugMessage(string $message, int $level = E_USER_WARNING);

    /**
     * Send exception if debug mode is enabled
     *
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     */
    public function debugRaiseException(string $message = null, int $code = null, \Throwable $previous = null);
}
