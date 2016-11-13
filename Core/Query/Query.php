<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ConnectionInterface;

/**
 * Represents a paginated query
 */
class PagerQuery
{
    private $connection;
    private $projection;
    private $sql;

    /**
     *
     * @param Projection $projection
     * @param ConnectionInterface $connection
     */
    public function __construct(Projection $projection, ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->projection = $projection;
        $this->where = new Where();
    }
}
