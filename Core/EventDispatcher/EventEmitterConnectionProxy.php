<?php

declare(strict_types=1);

namespace Goat\Core\EventDispatcher;

use Goat\Core\Client\AbstractConnectionProxy;
use Goat\Core\Client\ConnectionInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Connection proxy that emits events via Symfony's EventDispatcher
 */
class EventEmitterConnectionProxy extends AbstractConnectionProxy
{
    private $connection;
    private $eventDispatcher;

    /**
     * Default constructor
     *
     * @param ConnectionInterface $connection
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ConnectionInterface $connection, EventDispatcherInterface $eventDispatcher)
    {
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInnerConnection() : ConnectionInterface
    {
        return $this->connection;
    }
}
