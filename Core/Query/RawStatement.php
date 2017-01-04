<?php

namespace Goat\Core\Query;

/**
 * Represents a raw SQL statement, for internal use only
 */
class RawStatement
{
    private $statement;
    private $arguments = [];

    public function __construct($statement, array $arguments = [])
    {
        $this->statement = $statement;
        $this->arguments = $arguments;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getStatement()
    {
        return $this->statement;
    }

    public function __toString()
    {
        return $this->statement;
    }
}
