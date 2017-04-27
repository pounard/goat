<?php

declare(strict_types=1);

namespace Goat\Error;

/**
 * Driver specific error while running a query
 */
class DriverError extends GoatError
{
    private $rawSQL;
    private $parameters;

    /**
     * Default constructor
     *
     * @param string $rawSQL
     * @param array $parameters
     * @param \Throwable $previous
     */
    public function __construct($rawSQL, $parameters = null, \Throwable $previous = null)
    {
        $this->rawSQL = $rawSQL;
        $this->parameters = $parameters;

        $message = sprintf("error while querying backend, query is:\n%s", $rawSQL);

        parent::__construct($message, null, $previous);
    }
}
