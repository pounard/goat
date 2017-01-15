<?php

namespace Goat\Driver\PgSQL;

use Goat\Core\Error\DriverError;
use Goat\Core\Error\GoatError;

/**
 * ext_pgsql does not send any exceptions, so we have no previous exception
 * to display, we will use a custom implementation instead
 */
class PgSQLDriverError extends DriverError
{
    public function __construct($message, $code = null)
    {
        GoatError::__construct($message, $code);
    }
}
