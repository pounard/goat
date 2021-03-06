<?php

declare(strict_types=1);

namespace Goat\Driver\ExtPgSQL;

use Goat\Error\DriverError;
use Goat\Error\GoatError;

/**
 * ext_pgsql does not send any exceptions, so we have no previous exception
 * to display, we will use a custom implementation instead
 */
class ExtPgSQLDriverError extends DriverError
{
    public function __construct($message, $code = null)
    {
        GoatError::__construct($message, $code);
    }
}
