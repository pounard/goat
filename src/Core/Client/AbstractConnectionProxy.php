<?php

declare(strict_types=1);

namespace Goat\Core\Client;

use Goat\Converter\ConverterMap;
use Goat\Core\Hydrator\HydratorMap;
use Goat\Core\Transaction\Transaction;
use Goat\Query\DeleteQuery;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\SelectQuery;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Query\UpdateQuery;

/**
 * Connection proxy basis
 *
 * @codeCoverageIgnore
 *   We do ignore code coverage for this class at this point, because most
 *   implementations will voluntarily drop lots of methods of it.
 */
abstract class AbstractConnectionProxy implements ConnectionInterface
{
    /**
     * Get nested connection
     *
     * @return ConnectionInterface
     */
    abstract protected function getInnerConnection() : ConnectionInterface;

    /**
     * {@inheritdoc}
     */
    public function getDatabaseInfo() : array
    {
        return $this->getInnerConnection()->getDatabaseInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseName() : string
    {
        return $this->getInnerConnection()->getDatabaseName();
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName() : string
    {
        return $this->getInnerConnection()->getDriverName();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseVersion() : string
    {
        return $this->getInnerConnection()->getDatabaseVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning() : bool
    {
        return $this->getInnerConnection()->supportsReturning();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints() : bool
    {
        return $this->getInnerConnection()->supportsDeferingConstraints();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return $this->getInnerConnection()->close();
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false) : Transaction
    {
        return $this->getInnerConnection()->startTransaction($isolationLevel, $allowPending);
    }

    /**
     * {@inheritdoc}
     */
    public function isTransactionPending() : bool
    {
        return $this->getInnerConnection()->isTransactionPending();
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        return $this->getInnerConnection()->query($query, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, array $parameters = null, $options = null) : int
    {
        return $this->getInnerConnection()->perform($query, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null) : string
    {
        return $this->getInnerConnection()->prepareQuery($query, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        return $this->getInnerConnection()->executePreparedQuery($identifier, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function select($relation, string $alias = null) : SelectQuery
    {
        return $this->getInnerConnection()->select($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function update($relation, string $alias = null) : UpdateQuery
    {
        return $this->getInnerConnection()->update($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function insertValues($relation) : InsertValuesQuery
    {
        return $this->getInnerConnection()->insertValues($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($relation, string $alias = null) : DeleteQuery
    {
        return $this->getInnerConnection()->delete($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery($relation) : InsertQueryQuery
    {
        return $this->getInnerConnection()->insertQuery($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTables($relationNames)
    {
        return $this->getInnerConnection()->truncateTables($relationNames);
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding(string $encoding)
    {
        return $this->getInnerConnection()->setClientEncoding($encoding);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter() : FormatterInterface
    {
        return $this->getInnerConnection()->getFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterMap $converter)
    {
        return $this->getInnerConnection()->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function setHydratorMap(HydratorMap $hydratorMap)
    {
        return $this->getInnerConnection()->setHydratorMap($hydratorMap);
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugEnabled() : bool
    {
        return $this->getInnerConnection()->isDebugEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug(bool $debug = true)
    {
        return $this->getInnerConnection()->setDebug($debug);
    }

    /**
     * {@inheritdoc}
     */
    public function debugMessage(string $message, int $level = E_USER_WARNING)
    {
        return $this->getInnerConnection()->debugMessage($message, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function getEscaper() : EscaperInterface
    {
        return $this->getInnerConnection()->getEscaper();
    }

    /**
     * {@inheritdoc}
     */
    public function debugRaiseException(string $message = null, int $code = null, \Throwable $previous = null)
    {
        return $this->getInnerConnection()->debugRaiseException($message, $code, $previous);
    }
}
