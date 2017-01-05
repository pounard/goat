<?php

namespace Goat\Core\Query;

/**
 * Represents a select query
 *
 * @todo this needs to be plugged to an escaper, for literal escaping such as
 *   column names and relation names
 */
interface SqlFormatterInterface
{
    /**
     * Format projection for a single column or statement
     *
     * @param string|RawStatement $statement
     * @param string $alias
     *
     * @return string
     */
    public function formatProjection($statement, $alias = null);

    /**
     * Format the whole projection
     *
     * @param array $columns
     *   Each column is an array that must contain:
     *     - 0: string or RawStatement: column name or SQL statement
     *     - 1: column alias, can be empty or null for no aliasing
     *
     * @return string
     */
    public function formatProjectionAll(array $columns);

    /**
     * Format a single order by
     *
     * @param string $column
     * @param int $order
     *   Query::ORDER_* constant
     * @param int $null
     *   Query::NULL_* constant
     */
    public function formatOrderBy($column, $order, $null);

    /**
     * Format the whole order by clause
     *
     * @param $orders
     *   Each order is an array that must contain:
     *     - 0: string column name or alias
     *     - 1: Query::ORDER_* constant
     *     - 2: Query::NULL_* constant
     *
     * @return string
     */
    public function formatOrderByAll(array $orders);

    /**
     * Format a single group by clause
     *
     * @return string
     */
    public function formatGroupBy($column);

    /**
     * Format the whole group by clause
     *
     * @param $groups
     *   Array of column names or aliases
     *
     * @return string
     */
    public function formatGroupByAll(array $groups);

    /**
     * Format a single join statement
     *
     * @param int $mode
     *   Query::JOIN_* constant
     * @param string $relation
     *   Relation to join on name
     * @param string $alias
     *   Relation to join alias
     * @param Where $condition
     *   There where condition to join upon, it can be empty or null case in
     *   which this will leave the SQL engine doing a cartesian product with
     *   the tables
     *
     * @return string
     */
    public function formatJoin($mode, $relation, $alias, Where $condition);

    /**
     * Format all join statements
     *
     * @param array $joins
     *   Each join is an array that must contain:
     *     - key must be the relation alias
     *     - 0: string relation name
     *     - 1: Where or null condition
     *     - 2: Query::JOIN_* constant
     *
     * @return string
     */
    public function formatJoinAll(array $joins);

    /**
     * Format range statement
     *
     * @param int $limit
     *   O means no limit
     * @param int $offset
     *   0 means default offset
     */
    public function formatRange($limit = 0, $offset = 0);

    /**
     * Format the query
     *
     * @param SelectQuery $query
     *
     * @return string
     */
    public function format(SelectQuery $query);
}
