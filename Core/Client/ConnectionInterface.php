<?php

namespace Goat\Core\Client;

use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Converter\ConverterAwareInterface;
use Goat\Core\DebuggableInterface;
use Goat\Core\Error\TransactionError;
use Goat\Core\Hydrator\HydratorMap;
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
     * Send query
     *
     * @param string|Query $query
     *   If a query is given here, and parameters is empty, it will use the
     *   Query instance parameters, but if you provide parameters, it will
     *   override them
     * @param mixed[] $parameters
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return ResultIteratorInterface
     */
    public function query($query, array $parameters = null, $options = null) : ResultIteratorInterface;

    /**
     * Perform only, do not return a result but affected row count instead
     *
     * @param string|Query $query
     *   If a query is given here, and parameters is empty, it will use the
     *   Query instance parameters, but if you provide parameters, it will
     *   override them
     * @param mixed[] $parameters
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return int
     */
    public function perform($query, array $parameters = null, $options = null) : int;

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
    public function prepareQuery($query, string $identifier = null) : string;

    /**
     * Prepare query
     *
     * @param string $identifier
     *   Query unique identifier
     * @param mixed[] $parameters
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return ResultIteratorInterface
     */
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface;

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
     * Get last insert identifier
     *
     * @return int|string
     */
    // public function getLastInsertId();

    /**
     * Set connection encoding
     *
     * @param string $encoding
     */
    public function setClientEncoding(string $encoding);

    /**
     * Get SQL formatter
     *
     * @return SqlFormatterInterface
     */
    public function getSqlFormatter() : SqlFormatterInterface;

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
    public function getCastType(string $type) : string;

    /**
     * Set hydrator map
     *
     * @param HydratorMap $hydratorMap
     */
    public function setHydratorMap(HydratorMap $hydratorMap);
}
