<?php

namespace Goat\Core\Query;

use Goat\Core\Error\QueryError;
use Goat\Core\Query\Partial\FromClause;

/**
 * Represents a select query
 *
 * @todo this needs to be plugged to an escaper, for literal escaping such as
 *   column names and relation names
 */
class SelectQuery extends FromClause
{
    private $where;
    private $having;
    private $groups = [];
    private $orders = [];
    private $limit = 0;
    private $offset = 0;

    /**
     * Build a new query
     *
     * @param string $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function __construct($relation, $alias = null)
    {
        parent::__construct($relation, $alias);

        $this->where = new Where();
        $this->having = new Where();
    }

    /**
     * Get group by clauses array
     *
     * @return array
     */
    public function getAllGroupBy()
    {
        return $this->groups;
    }

    /**
     * Get order by clauses array
     *
     * @return array
     */
    public function getAllOrderBy()
    {
        return $this->orders;
    }

    /**
     * Get query range
     *
     * @return int[]
     *   First value is limit second is offset
     */
    public function getRange()
    {
        return [$this->limit, $this->offset];
    }

    /**
     * Add a condition in the where clause
     *
     * @param string $column
     * @param mixed $value
     * @param string $operator
     *
     * @return $this
     */
    public function condition($column, $value, $operator = Where::EQUAL)
    {
        $this->where->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the where clause
     *
     * @param string $statement
     *   SQL string, which may contain parameters
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     *
     * @return $this
     */
    public function statement($statement, $arguments = [])
    {
        $this->where->statement($statement, $arguments);

        return $this;
    }

    /**
     * Add a condition in the having clause
     *
     * @param string $column
     * @param mixed $value
     * @param string $operator
     *
     * @return $this
     */
    public function havingCondition($column, $value, $operator = Where::EQUAL)
    {
        $this->having->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the having clause
     *
     * @param string $statement
     *   SQL string, which may contain parameters
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     *
     * @return $this
     */
    public function havingStatement($statement, $arguments = [])
    {
        $this->having->statement($statement, $arguments);

        return $this;
    }

    /**
     * Get where statement
     *
     * @return Where
     */
    public function where()
    {
        return $this->where;
    }

    /**
     * Get having statement
     *
     * @return Where
     */
    public function having()
    {
        return $this->having;
    }

    /**
     * Add an order by clause
     *
     * @param string $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement
     * @param int $order
     *   One of the Query::ORDER_* constants
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default
     *
     * @return $this
     */
    public function orderBy($column, $order = Query::ORDER_ASC, $null = Query::NULL_IGNORE)
    {
        $this->orders[] = [$column, $order, $null];

        return $this;
    }

    /**
     * Add a group by clause
     *
     * @param string $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement
     *
     * @return $this
     */
    public function groupBy($column)
    {
        $this->groups[] = $column;

        return $this;
    }

    /**
     * Set limit/offset
     *
     * @param int $limit
     *   If empty or null, removes the current limit
     * @param int $offset
     *   If empty or null, removes the current offset
     *
     * @return $this
     */
    public function range($limit, $offset = 0)
    {
        if (!is_int($limit) || $limit < 0) {
            throw new QueryError("limit must be a positive integer");
        }
        if (!is_int($offset) || $offset < 0) {
            throw new QueryError("offset must be a positive integer");
        }

        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get query arguments
     *
     * @return string[]
     */
    public function getArguments()
    {
        return array_merge(
            $this->where->getArguments(),
            $this->having->getArguments()
        );
    }
}
