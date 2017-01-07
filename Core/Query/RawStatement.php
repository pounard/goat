<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;
use Goat\Core\Client\ArgumentHolderInterface;

/**
 * Represents a raw SQL statement, for internal use only
 */
class RawStatement implements ArgumentHolderInterface
{
    private $statement;
    private $arguments;

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
        $this->arguments = new ArgumentBag();
        $this->arguments->appendArray($parameters);
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
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->arguments = clone $this->arguments;
    }
}
