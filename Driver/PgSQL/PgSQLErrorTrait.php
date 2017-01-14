<?php

namespace Goat\Driver\PgSQL;

use Goat\Core\Error\GoatError;

trait PgSQLErrorTrait
{
    /**
     * Throw an exception if the given status is an error
     *
     * @param int $status
     */
    private function throwIfError(int $status)
    {
        if (PGSQL_BAD_RESPONSE === $status ||  PGSQL_FATAL_ERROR === $status) {
            throw new GoatError();
        }
    }
}
