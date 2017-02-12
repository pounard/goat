<?php

declare(strict_types=1);

namespace Goat\Core\Client;

interface ConnectionAwareInterface
{
    /**
     * Set connection
     *
     * @param ConnectionInterface $connection
     */
    public function setConnection(ConnectionInterface $connection);
}
