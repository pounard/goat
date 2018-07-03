<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Error\ConfigurationError;
use Goat\Query\SelectQuery;
use Goat\Runner\ResultIteratorInterface;

/**
 * Facade connection that handles read and write connection for you.
 */
class Session extends AbstractDriverProxy
{
    private $readonlyConnection;
    private $writeConnection;

    /**
     * Default constructor
     *
     * @param DriverInterface $writeConnection
     * @param DriverInterface $readonlyConnection
     */
    public function __construct(DriverInterface $writeConnection, DriverInterface $readonlyConnection = null)
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
     * @return DriverInterface
     */
    public function getReadonlyDriver() : DriverInterface
    {
        if ($this->readonlyConnection) {
            return $this->readonlyConnection;
        }

        return $this->writeConnection;
    }

    /**
     * Get writeable connection
     *
     * @return DriverInterface
     */
    public function getWriteDriver() : DriverInterface
    {
        return $this->writeConnection;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInnerDriver() : DriverInterface
    {
        return $this->writeConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug(bool $debug = true)
    {
        if ($this->readonlyConnection) {
            $this->readonlyConnection->setDebug($debug);
        }
        $this->writeConnection->setDebug($debug);
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugEnabled() : bool
    {
        return $this->writeConnection->isDebugEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->readonlyConnection) {
            $this->readonlyConnection->close();
        }
        $this->writeConnection->close();
    }

    /**
     * {@inheritdoc}
     */
    public function execute($query, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->execute($query, $parameters, $options);
        }

        return $this->writeConnection->execute($query, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, array $parameters = null, $options = null) : int
    {
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->perform($query, $parameters, $options);
        }

        return $this->writeConnection->perform($query, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null) : string
    {
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->prepareQuery($query, $identifier);
        }

        return $this->writeConnection->prepareQuery($query, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->executePreparedQuery($identifier, $parameters, $options);
        }

        return $this->writeConnection->executePreparedQuery($identifier, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function select($relation, string $alias = null) : SelectQuery
    {
        if ($this->readonlyConnection && !$this->isTransactionPending()) {
            return $this->readonlyConnection->select($relation, $alias);
        }

        return $this->writeConnection->select($relation, $alias);
    }
}
