<?php

namespace Goat\Core\Query;

/**
 * Represents a raw SQL statement, for internal use only
 */
class RawStatement
{
    private $statement;
    private $parameters = [];

    /**
     * Default constructor
     *
     * @param string $statement
     *   Raw SQL string
     * @param array $parameters
     *   Key/value pairs or argument list, anonymous and named parameters
     *   cannot be mixed up within the same query
     */
    public function __construct($statement, array $parameters = [])
    {
        $this->statement = $statement;
        $this->parameters = $parameters;
    }

    /**
     * Get query arguments
     *
     * @return array
     *   Key/value pairs or argument list
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get raw SQL string
     *
     * @return string
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Get raw SQL string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->statement;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        foreach ($this->parameters as $index => $value) {
            if (is_object($value)) {
                $this->parameters[$index] = clone $value;
            }
        }

        if (is_object($this->statement)) {
            $this->statement = clone $this->statement;
        }
    }
}
