<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ConnectionAwareTrait;
use Goat\Core\Error\GoatError;
use Goat\Core\Error\NotImplementedError;

trait QueryTrait
{
    use ConnectionAwareTrait;

    /**
     * Execute query with the given parameters and return the result iterator
     *
     * @param array $parameters
     *   Key/value pairs or argument list, anonymous and named parameters
     *   cannot be mixed up within the same query
     * @param string $class
     *   Object class that the iterator should return
     *
     * @return ResultIteratorInterface
     */
    public function execute($class = Query::RET_PROXY, array $parameters = [])
    {
        if (!$this->connection) {
            throw new GoatError("this query has no reference to any connection, therefore cannot execute itself");
        }

        return $this->connection->query(
            $this->connection->getSqlFormatter()->format($this),
            $this->getArguments()
        );
    }
}
