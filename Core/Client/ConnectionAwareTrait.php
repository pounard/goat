<?php

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
     *
     * @return $this
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }
}
