<?php

declare(strict_types=1);

namespace Goat\Core\EventDispatcher;

use Goat\Core\Client\AbstractConnectionProxy;
use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Transaction\Transaction;

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
     * Get event dispatcher
     *
     * @return EventDispatcherInterface
     */
    final public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInnerConnection() : ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        $ret = $this->getInnerConnection()->query($query, $parameters ?? [], $options);

        $this->eventDispatcher->dispatch(GoatEvents::QUERY, new GoatEvent($this));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, array $parameters = null, $options = null) : int
    {
        $ret = $this->getInnerConnection()->perform($query, $parameters ?? [], $options);

        $this->eventDispatcher->dispatch(GoatEvents::QUERY, new GoatEvent($this));
        $this->eventDispatcher->dispatch(GoatEvents::PERFORM, new GoatEvent($this));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null) : string
    {
        $ret = $this->getInnerConnection()->prepareQuery($query, $identifier);

        $this->eventDispatcher->dispatch(GoatEvents::PREPARE, new GoatEvent($this));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        $ret = $this->getInnerConnection()->executePreparedQuery($identifier, $parameters ?? [], $options);

        $this->eventDispatcher->dispatch(GoatEvents::QUERY, new GoatEvent($this));
        $this->eventDispatcher->dispatch(GoatEvents::PREPARE_EXECUTE, new GoatEvent($this));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false) : Transaction
    {
        $ret = $this->getInnerConnection()->startTransaction($isolationLevel, $allowPending);

        $this->eventDispatcher->dispatch(GoatEvents::TRANSACTION_START, new GoatEvent($this));

        return $ret;
    }
}
