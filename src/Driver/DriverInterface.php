<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Converter\ConverterAwareInterface;
use Goat\Core\DebuggableInterface;
use Goat\Error\TransactionError;
use Goat\Hydrator\HydratorMap;
use Goat\Query\DeleteQuery;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\Query;
use Goat\Query\SelectQuery;
use Goat\Query\UpdateQuery;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\RunnerInterface;
use Goat\Runner\Transaction;

interface DriverInterface extends ConverterAwareInterface, DebuggableInterface, RunnerInterface
{
    /**
     * Get database server information
     *
     * @return string[]
     */
    public function getDatabaseInfo() : array;

    /**
     * Get database server name
     *
     * @return string
     */
    public function getDatabaseName() : string;

    /**
     * Get driver name
     *
     * @return string
     */
    public function getDriverName() : string;

    /**
     * Get database version if found
     *
     * @return string
     */
    public function getDatabaseVersion() : string;

    /**
     * Does the backend supports RETURNING clauses
     *
     * @return bool
     */
    public function supportsReturning() : bool;

    /**
     * Does the backend supports defering constraints
     *
     * @return bool
     */
    public function supportsDeferingConstraints() : bool;

    /**
     * Close connection
     */
    public function close();

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
     * @param bool $allowPending = false
     *   If set to true, explicitely allow to fetch the currently pending
     *   transaction, else errors will be raised
     *
     * @throws TransactionError
     *   If you asked a new transaction while another one is opened, or if the
     *   transaction fails starting
     *
     * @return Transaction
     */
    public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false) : Transaction;

    /**
     * Is there a pending transaction
     *
     * @return bool
     */
    public function isTransactionPending() : bool;

    /**
     * Create a select query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return SelectQuery
     */
    public function select($relation, string $alias = null) : SelectQuery;

    /**
     * Create an update query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return UpdateQuery
     */
    public function update($relation, string $alias = null) : UpdateQuery;

    /**
     * Create an insert query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     *
     * @return InsertValuesQuery
     */
    public function insertValues($relation) : InsertValuesQuery;

    /**
     * Create an insert with query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     *
     * @return InsertQueryQuery
     */
    public function insertQuery($relation) : InsertQueryQuery;

    /**
     * Create a delete query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return DeleteQuery
     */
    public function delete($relation, string $alias = null) : DeleteQuery;

    /**
     * Truncate given tables (warning, it does it right away)
     *
     * @todo
     *   - move this out into a ddl specific object
     *   - SQL 92 standard is about one table at a time, PgSQL can do multiple at once
     *
     * @param string|string[] $relationNames
     *   Either one or more table names
     */
    public function truncateTables($relationNames);

    /**
     * Set connection encoding
     *
     * @param string $encoding
     */
    public function setClientEncoding(string $encoding);

    /**
     * Get SQL formatter
     *
     * @return FormatterInterface
     */
    public function getFormatter() : FormatterInterface;

    /**
     * Get SQL escaper
     *
     * @return EscaperInterface
     */
    public function getEscaper() : EscaperInterface;

    /**
     * Set hydrator map
     *
     * @param HydratorMap $hydratorMap
     */
    public function setHydratorMap(HydratorMap $hydratorMap);
}
