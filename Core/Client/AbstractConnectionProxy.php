<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterMap;
use Goat\Core\Transaction\Transaction;

/**
 * Connection proxy that emits events via Symfony's EventDispatcher
 */
abstract class AbstractConnectionProxy implements ConnectionInterface
{
    /**
     * Get nested connection
     *
     * @return ConnectionInterface
     */
    abstract protected function getInnerConnection();

    /**
     * {@inheritdoc}
     */
    public function supportsReturning()
    {
        return $this->getInnerConnection()->supportsReturning();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints()
    {
        return $this->getInnerConnection()->supportsDeferingConstraints();
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction($isolationLevel = Transaction::REPEATABLE_READ, $allowPending = false)
    {
        return $this->getInnerConnection()->startTransaction($isolationLevel, $allowPending);
    }

    /**
     * {@inheritdoc}
     */
    public function isTransactionPending()
    {
        return $this->getInnerConnection()->isTransactionPending();
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, $parameters = null, $enableConverters = true)
    {
        return $this->getInnerConnection()->query($query, $parameters, $enableConverters);
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $parameters = null)
    {
        return $this->getInnerConnection()->perform($query, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, $identifier = null)
    {
        return $this->getInnerConnection()->prepareQuery($query, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery($identifier, $parameters = null, $enableConverters = true)
    {
        return $this->getInnerConnection()->executePreparedQuery($identifier, $parameters, $enableConverters);
    }

    /**
     * {@inheritdoc}
     */
    // public function getLastInsertId()
    // {
    //     return $this->getInnerConnection()->getLastInsertId();
    // }

    /**
     * {@inheritdoc}
     */
    public function select($relation, $alias = null)
    {
        return $this->getInnerConnection()->select($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function update($relation, $alias = null)
    {
        return $this->getInnerConnection()->update($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function insertValues($relation)
    {
        return $this->getInnerConnection()->insertValues($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($relation, $alias = null)
    {
        return $this->getInnerConnection()->delete($relation, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery($relation)
    {
        return $this->getInnerConnection()->insertQuery($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTables($relations)
    {
        return $this->getInnerConnection()->truncateTables($relations);
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding($encoding)
    {
        return $this->getInnerConnection()->setClientEncoding($encoding);
    }

    /**
     * {@inheritdoc}
     */
    public function getSqlFormatter()
    {
        return $this->getInnerConnection()->getSqlFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function getCastType($type)
    {
        return $this->getInnerConnection()->getCastType($type);
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
    public function escapeIdentifier($string)
    {
        return $this->getInnerConnection()->escapeIdentifier($string);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifierList($strings)
    {
        return $this->getInnerConnection()->escapeIdentifierList($strings);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral($string)
    {
        return $this->getInnerConnection()->escapeLiteral($string);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob($word)
    {
        return $this->getInnerConnection()->escapeBlob($word);
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugEnabled()
    {
        return $this->getInnerConnection()->isDebugEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug($debug = true)
    {
        return $this->getInnerConnection()->setDebug($debug);
    }

    /**
     * {@inheritdoc}
     */
    public function debugMessage($message, $level = E_USER_WARNING)
    {
        return $this->getInnerConnection()->debugMessage($message, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function debugRaiseException($message = null, $code = null, $previous = null)
    {
        return $this->getInnerConnection()->debugRaiseException($message, $code, $previous);
    }
}
