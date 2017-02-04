<?php

declare(strict_types=1);

namespace Goat\Driver\PgSQL;

trait PgSQLErrorTrait
{
    /**
     * Throw an exception if the given status is an error
     *
     * @param resource $result
     */
    private function resultError($result)
    {
        $status = pg_result_status($this->resource);

        if (PGSQL_BAD_RESPONSE === $status ||  PGSQL_FATAL_ERROR === $status) {
            $errorString = pg_result_error($result);
            if (false === $errorString) {
                throw new PgSQLDriverError("unknown error: could not fetch status code");
            } else {
                throw new PgSQLDriverError($errorString, $status);
            }
        }
    }

    /**
     * Throw an exception if the given status is an error
     *
     * @param resource $resource
     * @param string $rawSQL
     */
    private function connectionError($resource = null, string $rawSQL = null)
    {
        $errorString = pg_last_error($resource);
        if (false === $errorString) {
            $errorString = "unknown error: could not fetch status code";
            if ($rawSQL) {
                $errorString .= ', query was: ' .$rawSQL;
            }
            throw new PgSQLDriverError($errorString);
        } else {
            if ($rawSQL) {
                $errorString .= ', query was: ' .$rawSQL;
            }
            throw new PgSQLDriverError($errorString, (int)pg_connection_status($resource));
        }
    }
}
