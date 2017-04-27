<?php

declare(strict_types=1);

namespace Goat\Error;

/**
 * Generic API error, all errors from this API inherit from this.
 */
class GoatError extends \RuntimeException
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
        // PHP with strict_types is a bit of a nazi when it comes to exceptions
        if (null === $message) {
            $message = '';
        }

        if ($previous) {
            if (null === $code) {
                // Sadly some internal exceptions, especially \PDOException
                // instances will give a string instead of an int as the
                // error code, so we have to cast.
                $code = $previous->getCode();
                if (!is_int($code)) {
                    $code = 0;
                }
            }
            parent::__construct($message, $code, $previous);
        } else {
            parent::__construct($message);
        }
    }
}
