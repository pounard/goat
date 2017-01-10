<?php

namespace Goat\Core\Error;

class DriverError extends GoatError
{
    private $rawSQL;
    private $parameters;

    /**
     * Default constructor
     *
     * @param string $rawSQL
     * @param array $parameters
     * @param \Exception|\Error $previous
     */
    public function __construct($rawSQL, $parameters, $previous)
    {
        $this->rawSQL = $rawSQL;
        $this->parameters = $parameters;

        $message = sprintf("error while querying backend, query is:\n%s", $rawSQL);

        parent::__construct($message, null, $previous);
    }
}
