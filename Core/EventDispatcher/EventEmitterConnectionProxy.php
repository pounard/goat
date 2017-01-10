<?php

namespace Goat\Core\EventDispatcher;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Converter\ConverterMap;
use Goat\Core\Transaction\Transaction;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Connection proxy that emits events via Symfony's EventDispatcher
 */
class EventEmitterConnectionProxy implements ConnectionInterface
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
    public function supportsReturning()
    {
        return $this->connection->supportsReturning();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints()
    {
        return $this->connection->supportsDeferingConstraints();
    }

    /**
     * {@inheritdoc}
     */
    public function transaction($isolationLevel = Transaction::REPEATABLE_READ)
    {
        return $this->connection->transaction($isolationLevel);
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, $parameters = null, $enableConverters = true)
    {
        return $this->connection->query($query, $parameters, $enableConverters);
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $parameters = null)
    {
        return $this->connection->perform($query, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, $identifier = null)
    {
        return $this->connection->prepareQuery($query, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery($identifier, $parameters = null, $enableConverters = true)
    {
        return $this->connection->executePreparedQuery($identifier, $parameters, $enableConverters);
    }

    /**
     * {@inheritdoc}
     */
    // public function getLastInsertId()
    // {
    //     return $this->connection->getLastInsertId();
    // }

    /**
     * {@inheritdoc}
     */
    public function select($relation, $alias = null)
    {
        return $this->connection->select($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function update($relation, $alias = null)
    {
        return $this->connection->update($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function insertValues($relation)
    {
        return $this->connection->insertValues($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery($relation)
    {
        return $this->connection->insertQuery($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding($encoding)
    {
        return $this->connection->setClientEncoding($encoding);
    }

    /**
     * {@inheritdoc}
     */
    public function getSqlFormatter()
    {
        return $this->connection->getSqlFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function getCastType($type)
    {
        return $this->connection->getCastType($type);
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterMap $converter)
    {
        return $this->connection->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier($string)
    {
        return $this->connection->escapeIdentifier($string);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral($string)
    {
        return $this->connection->escapeLiteral($string);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob($word)
    {
        return $this->connection->escapeBlob($word);
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugEnabled()
    {
        return $this->connection->isDebugEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug($debug = true)
    {
        return $this->connection->setDebug($debug);
    }

    /**
     * {@inheritdoc}
     */
    public function debugMessage($message, $level = E_USER_WARNING)
    {
        return $this->connection->debugMessage($message, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function debugRaiseException($message = null, $code = null, $previous = null)
    {
        return $this->connection->debugRaiseException($message, $code, $previous);
    }
}
