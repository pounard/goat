<?php

namespace Goat\Core\Client;

use Goat\Core\Client\ArgumentBag;
use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Converter\ConverterAwareInterface;
use Goat\Core\DebuggableInterface;
use Goat\Core\Error\TransactionError;
use Goat\Core\Query\DeleteQuery;
use Goat\Core\Query\InsertQueryQuery;
use Goat\Core\Query\InsertValuesQuery;
use Goat\Core\Query\Query;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\SqlFormatterInterface;
use Goat\Core\Query\UpdateQuery;
use Goat\Core\Transaction\Transaction;

interface ConnectionInterface extends ConverterAwareInterface, EscaperInterface, DebuggableInterface
{
    /**
     * Does the backend supports RETURNING clauses
     *
     * @return boolean
     */
    public function supportsReturning();

    /**
     * Does the backend supports defering constraints
     *
     * @return boolean
     */
    public function supportsDeferingConstraints();

    /**
     * Creates a new transaction
     *
     * If a transaction is pending, continue the same transaction by adding a
     * new savepoint that will be transparently rollbacked in case of failure
     * in between.
     *
     * @param int $isolationLevel
     *   Default transaction isolation level, it is advised that you set it
     *   directly at this point, since some drivers don't allow isolation
     *   level changes while transaction is started
     * @param boolean $allowPending = false
     *   If set to true, explicitely allow to fetch the currently pending
     *   transaction, else errors will be raised
     *
     * @throws TransactionError
     *   If you asked a new transaction while another one is opened, or if the
     *   transaction fails starting
     *
     * @return Transaction
     */
    public function startTransaction($isolationLevel = Transaction::REPEATABLE_READ, $allowPending = false);

    /**
     * Is there a pending transaction
     *
     * @return boolean
     */
    public function isTransactionPending();

    /**
     * Send query
     *
     * @param string|Query $query
     *   If a query is given here, and parameters is empty, it will use the
     *   Query instance parameters, but if you provide parameters, it will
     *   override them
     * @param mixed[]|ArgumentBag $parameters
     *   Query parameters
     * @param boolean $enableConverters
     *   If set to false, converters would be disabled
     *
     * @return ResultIteratorInterface
     */
    public function query($query, $parameters = null, $enableConverters = true);

    /**
     * Perform only, do not return a result but affected row count instead
     *
     * @param string|Query $query
     *   If a query is given here, and parameters is empty, it will use the
     *   Query instance parameters, but if you provide parameters, it will
     *   override them
     * @param mixed[]|ArgumentBag $parameters
     *   Query parameters
     *
     * @return int
     */
    public function perform($query, $parameters = null);

    /**
     * Prepare query
     *
     * @param string|Query $query
     *   Bare SQL or Query instance
     * @param string $identifier
     *   Query unique identifier, if null given one will be generated
     *
     * @return string
     *   The given or generated identifier
     */
    public function prepareQuery($query, $identifier = null);

    /**
     * Prepare query
     *
     * @param string $identifier
     *   Query unique identifier
     * @param mixed[]|ArgumentBag $parameters
     *   Query parameters
     * @param boolean $enableConverters
     *   If set to false, converters would be disabled
     *
     * @return ResultIteratorInterface
     */
    public function executePreparedQuery($identifier, $parameters = null, $enableConverters = true);

    /**
     * Create a select query builder
     *
     * @param string $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return SelectQuery
     */
    public function select($relation, $alias = null);

    /**
     * Create an update query builder
     *
     * @param string $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return UpdateQuery
     */
    public function update($relation, $alias = null);

    /**
     * Create an insert query builder
     *
     * @param string $relation
     *   SQL from statement relation name
     *
     * @return InsertValuesQuery
     */
    public function insertValues($relation);

    /**
     * Create an insert with query builder
     *
     * @param string $relation
     *   SQL from statement relation name
     *
     * @return InsertQueryQuery
     */
    public function insertQuery($relation);

    /**
     * Create a delete query builder
     *
     * @param string $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return DeleteQuery
     */
    public function delete($relation, $alias = null);

    /**
     * Get last insert identifier
     *
     * @return scalar
     */
    // public function getLastInsertId();

    /**
     * Set connection encoding
     *
     * @param string $encoding
     */
    public function setClientEncoding($encoding);

    /**
     * Get SQL formatter
     *
     * @return SqlFormatterInterface
     */
    public function getSqlFormatter();

    /**
     * Allows the driver to proceed to different type cast
     *
     * Use this if you want to keep a default implementation for a specific
     * type and don't want to override it.
     *
     * @param string $type
     *   The internal type carried by converters
     *
     * @return string
     *   The real type the server will understand
     */
    public function getCastType($type);
}
