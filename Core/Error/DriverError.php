<?php

namespace Goat\Core\Error;

class DriverError extends GoatError
{
    private $sql;
    private $parameters;

    /**
     * Default constructor
     *
     * @param string $sql
     * @param array $parameters
     * @param \Exception|\Error $previous
     */
    public function __construct($sql, $parameters, $previous)
    {
        $message = sprintf("error while querying backend, query is: %s", $sql);

        parent::__construct($message, null, $previous);
    }
}
