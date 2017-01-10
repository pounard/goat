<?php

namespace Goat\Core\Client;

use Goat\Core\Error\ConfigurationError;
use Goat\Core\Transaction\Transaction;
use Goat\Core\Converter\ConverterMap;

/**
 * Facade connection that handles read and write connection for you.
 */
class Session implements ConnectionInterface
{
    private $readonlyConnection;
    private $writeConnection;

    /**
     * Default constructor
     *
     * @param ConnectionInterface $writeConnection
     * @param ConnectionInterface $readonlyConnection
     */
    public function __construct(ConnectionInterface $writeConnection, ConnectionInterface $readonlyConnection = null)
    {
        if ($readonlyConnection) {
            if (get_class($readonlyConnection) !== get_class($writeConnection)) {
                throw new ConfigurationError(sprintf("Readonly and write connections are not using the same driver"));
            }

            $this->readonlyConnection = $readonlyConnection;
        }

        $this->writeConnection = $writeConnection;
    }

    /**
     * Get readonly connection
     *
     * @return ConnectionInterface
     */
    public function getReadonlyConnection()
    {
        if ($this->readonlyConnection) {
            return $this->readonlyConnection;
        }

        return $this->writeConnection;
    }

    /**
     * Get writeable connection
     *
     * @return ConnectionInterface
     */
    public function getWriteConnection()
    {
        return $this->writeConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning()
    {
        return $this->writeConnection->supportsReturning();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints()
    {
        return $this->writeConnection->supportsDeferingConstraints();
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction($isolationLevel = Transaction::REPEATABLE_READ, $allowPending = false)
    {
        return $this->writeConnection->startTransaction($isolationLevel, $allowPending);
    }

    /**
     * {@inheritdoc}
     */
    public function isTransactionPending()
    {
        return $this->writeConnection->isTransactionPending();
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, $parameters = null, $enableConverters = true)
    {
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->query($query, $parameters, $enableConverters);
        }

        return $this->writeConnection->query($query, $parameters, $enableConverters);
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $parameters = null)
    {
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->query($query, $parameters);
        }

        return $this->writeConnection->perform($query, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, $identifier = null)
    {
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->query($query, $identifier);
        }

        return $this->writeConnection->prepareQuery($query, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery($identifier, $parameters = null, $enableConverters = true)
    {
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->query($identifier, $parameters, $enableConverters);
        }

        return $this->writeConnection->executePreparedQuery($identifier, $parameters, $enableConverters);
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
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->query($relation, $alias);
        }

        return $this->writeConnection->select($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function update($relation, $alias = null)
    {
        return $this->writeConnection->update($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function insertValues($relation)
    {
        return $this->writeConnection->insertValues($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery($relation)
    {
        return $this->writeConnection->insertQuery($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding($encoding)
    {
        return $this->writeConnection->setClientEncoding($encoding);
    }

    /**
     * {@inheritdoc}
     */
    public function getSqlFormatter()
    {
        return $this->writeConnection->getSqlFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function getCastType($type)
    {
        return $this->writeConnection->getCastType($type);
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterMap $converter)
    {
        return $this->writeConnection->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier($string)
    {
        return $this->writeConnection->escapeIdentifier($string);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral($string)
    {
        return $this->writeConnection->escapeLiteral($string);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob($word)
    {
        return $this->writeConnection->escapeBlob($word);
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugEnabled()
    {
        return $this->writeConnection->isDebugEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug($debug = true)
    {
        return $this->writeConnection->setDebug($debug);
    }

    /**
     * {@inheritdoc}
     */
    public function debugMessage($message, $level = E_USER_WARNING)
    {
        return $this->writeConnection->debugMessage($message, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function debugRaiseException($message = null, $code = null, $previous = null)
    {
        return $this->writeConnection->debugRaiseException($message, $code, $previous);
    }
}
