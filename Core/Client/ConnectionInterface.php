<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareInterface;
use Goat\Core\Query\InsertQueryQuery;
use Goat\Core\Query\InsertValuesQuery;
use Goat\Core\Query\Query;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\SqlFormatterInterface;

interface ConnectionInterface extends ConverterAwareInterface, EscaperInterface
{
    /**
     * Does the backend supports RETURNING clauses
     *
     * @return boolean
     */
    public function supportsReturning();

    /**
     * Send query
     *
     * @param string|Query $query
     *   If a query is given here, and parameters is empty, it will use the
     *   Query instance parameters, but if you provide parameters, it will
     *   override them
     * @param mixed[] $parameters
     *   Query parameters
     * @param boolean $enableConverters
     *   If set to false, converters would be disabled
     *
     * @return ResultIteratorInterface
     */
    public function query($query, array $parameters = [], $enableConverters = true);

    /**
     * Perform only, do not return a result but affected row count instead
     *
     * @param string|Query $query
     *   If a query is given here, and parameters is empty, it will use the
     *   Query instance parameters, but if you provide parameters, it will
     *   override them
     * @param mixed[] $parameters
     *   Query parameters
     *
     * @return int
     */
    public function perform($query, array $parameters = []);

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
     * @param mixed[] $parameters
     *   Query parameters
     * @param boolean $enableConverters
     *   If set to false, converters would be disabled
     *
     * @return ResultIteratorInterface
     */
    public function executePreparedQuery($identifier, array $parameters = [], $enableConverters = true);

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
