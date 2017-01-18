<?php

declare(strict_types=1);

namespace Goat\Core\EventDispatcher;

use Goat\Core\Client\ConnectionInterface;

use Symfony\Component\EventDispatcher\Event;

/**
 * Default event class for this API events
 */
class GoatEvent extends Event
{
    private $connection;

    /**
     * Build event
     *
     * @param ConnectionInterface $connection
     * @param Timer $timer
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get connection
     *
     * @return ConnectionInterface
     */
    final public function getConnection() : ConnectionInterface
    {
        return $this->connection;
    }
}
