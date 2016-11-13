<?php

namespace Goat\Core\Client;

interface ConnectionAwareInterface
{
    /**
     * Set connection
     *
     * @param ConnectionInterface $connection
     *
     * @return $this
     */
    public function setConnection(ConnectionInterface $connection);
}
