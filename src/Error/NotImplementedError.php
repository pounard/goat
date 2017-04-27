<?php

declare(strict_types=1);

namespace Goat\Error;

/**
 * Non implemented feature
 */
class NotImplementedError extends GoatError
{
    /**
     * Default constructor
     *
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     */
    public function __construct(string $message = null, int $code = null, \Throwable $previous = null)
    {
        if (!$message) {
            $message = sprintf("this method is not implemented");
        }

        parent::__construct($message, $code, $previous);
    }
}
