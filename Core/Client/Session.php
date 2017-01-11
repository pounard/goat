<?php

namespace Goat\Core\Client;

use Goat\Core\Error\ConfigurationError;

/**
 * Facade connection that handles read and write connection for you.
 */
class Session extends AbstractConnectionProxy
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
    protected function getInnerConnection()
    {
        return $this->writeConnection;
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
}
