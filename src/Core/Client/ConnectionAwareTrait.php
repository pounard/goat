<?php

declare(strict_types=1);

namespace Goat\Core\Client;

trait ConnectionAwareTrait
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * Set connection
     *
     * @param ConnectionInterface $connection
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }
}
