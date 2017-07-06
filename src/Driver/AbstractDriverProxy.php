<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Converter\ConverterMap;
use Goat\Hydrator\HydratorMap;
use Goat\Query\DeleteQuery;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\SelectQuery;
use Goat\Query\UpdateQuery;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\ResultIteratorInterface;
use Goat\Runner\Transaction;

/**
 * Connection proxy basis
 *
 * @codeCoverageIgnore
 *   We do ignore code coverage for this class at this point, because most
 *   implementations will voluntarily drop lots of methods of it.
 */
abstract class AbstractDriverProxy implements DriverInterface
{
    /**
     * Get nested connection
     *
     * @return DriverInterface
     */
    abstract protected function getInnerDriver() : DriverInterface;

    /**
     * {@inheritdoc}
     */
    public function getDatabaseInfo() : array
    {
        return $this->getInnerDriver()->getDatabaseInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseName() : string
    {
        return $this->getInnerDriver()->getDatabaseName();
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName() : string
    {
        return $this->getInnerDriver()->getDriverName();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseVersion() : string
    {
        return $this->getInnerDriver()->getDatabaseVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning() : bool
    {
        return $this->getInnerDriver()->supportsReturning();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints() : bool
    {
        return $this->getInnerDriver()->supportsDeferingConstraints();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return $this->getInnerDriver()->close();
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false) : Transaction
    {
        return $this->getInnerDriver()->startTransaction($isolationLevel, $allowPending);
    }

    /**
     * {@inheritdoc}
     */
    public function isTransactionPending() : bool
    {
        return $this->getInnerDriver()->isTransactionPending();
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        return $this->getInnerDriver()->query($query, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, array $parameters = null, $options = null) : int
    {
        return $this->getInnerDriver()->perform($query, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null) : string
    {
        return $this->getInnerDriver()->prepareQuery($query, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        return $this->getInnerDriver()->executePreparedQuery($identifier, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function select($relation, string $alias = null) : SelectQuery
    {
        return $this->getInnerDriver()->select($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function update($relation, string $alias = null) : UpdateQuery
    {
        return $this->getInnerDriver()->update($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function insertValues($relation) : InsertValuesQuery
    {
        return $this->getInnerDriver()->insertValues($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($relation, string $alias = null) : DeleteQuery
    {
        return $this->getInnerDriver()->delete($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery($relation) : InsertQueryQuery
    {
        return $this->getInnerDriver()->insertQuery($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTables($relationNames)
    {
        return $this->getInnerDriver()->truncateTables($relationNames);
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding(string $encoding)
    {
        return $this->getInnerDriver()->setClientEncoding($encoding);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter() : FormatterInterface
    {
        return $this->getInnerDriver()->getFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterMap $converter)
    {
        return $this->getInnerDriver()->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function setHydratorMap(HydratorMap $hydratorMap)
    {
        return $this->getInnerDriver()->setHydratorMap($hydratorMap);
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug(bool $debug = true)
    {
        return $this->getInnerDriver()->setDebug($debug);
    }

    /**
     * {@inheritdoc}
     */
    public function getEscaper() : EscaperInterface
    {
        return $this->getInnerDriver()->getEscaper();
    }
}
