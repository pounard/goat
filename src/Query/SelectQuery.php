<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Core\Error\QueryError;
use Goat\Query\ArgumentBag;
use Goat\Query\ArgumentHolderInterface;
use Goat\Query\Partial\AbstractQuery;
use Goat\Query\Partial\FromClauseTrait;

/**
 * Represents a SELECT query
 *
 * @todo
 *   - support a SelectQuery as FROM relation
 *   - implement __clone() once this done
 */
final class SelectQuery extends AbstractQuery
{
    use FromClauseTrait;

    private $columns = [];
    private $forUpdate = false;
    private $groups = [];
    private $having;
    private $limit = 0;
    private $offset = 0;
    private $orders = [];
    private $performOnly = false;
    private $relation;
    private $relationAlias;
    private $where;

    /**
     * Build a new query
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function __construct($relation, string $alias = null)
    {
        parent::__construct($relation, $alias);

        $this->having = new Where();
        $this->where = new Where();
    }

    /**
     * Set the query as a SELECT ... FOR UPDATE query
     *
     * @return $this
     */
    public function forUpdate()
    {
        $this->forUpdate = true;

        return $this;
    }

    /**
     * Is this a SELECT ... FOR UPDATE
     *
     * @return bool
     */
    public function isForUpdate() : bool
    {
        return $this->forUpdate;
    }

    /**
     * Explicitely tell to the driver we don't want any return
     *
     * @return $this
     */
    public function performOnly()
    {
        $this->performOnly = true;

        return $this;
    }

    /**
     * Get select columns array
     *
     * @return array
     */
    public function getAllColumns() : array
    {
        return $this->columns;
    }

    /**
     * Remove everything from the current SELECT clause
     *
     * @return $this
     */
    public function removeAllColumns()
    {
        $this->columns = [];

        return $this;
    }

    /**
     * Remove everything from the current ORDER clause
     *
     * @return $this
     */
    public function removeAllOrder() : SelectQuery
    {
        $this->orders = [];

        return $this;
    }

    /**
     * Add a selected column
     *
     * If you need to pass arguments, use a Expression instance or columnExpression().
     *
     * @param string|Expression $expression
     *   SQL select column
     * @param string
     *   If alias to be different from the column
     *
     * @return $this
     */
    public function column($expression, string $alias = null)
    {
        if (!$expression instanceof Expression) {
            $expression = new ExpressionColumn($expression);
        }

        $this->columns[] = [$expression, $alias];

        return $this;
    }

    /**
     * Add a selected column as a raw SQL expression
     *
     * @param string|Expression $expression
     *   SQL select column
     * @param string
     *   If alias to be different from the column
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     *
     * @return $this
     */
    public function columnExpression($expression, string $alias = null, $arguments = [])
    {
        if ($expression instanceof Expression) {
            if ($arguments) {
                throw new QueryError(sprintf("you cannot call %s::columnExpression() and pass arguments if the given expression is not a string", __CLASS__));
            }
        } else {
            if (!is_array($arguments)) {
                $arguments = [$arguments];
            }
            $expression = new ExpressionRaw($expression, $arguments);
        }

        $this->columns[] = [$expression, $alias];

        return $this;
    }

    /**
     * Set or replace multiple columns at once
     *
     * @param string[] $columns
     *   Keys are aliases, values are SQL statements; if you do not wish to
     *   set aliases, keep the numeric indexes, if you want to use an integer
     *   as alias, just write it as a string, for example: "42".
     *
     * @return $this
     */
    public function columns(array $columns)
    {
        foreach ($columns as $alias => $statement) {
            if (is_int($alias)) {
                $this->column($statement);
            } else {
                $this->column($statement, $alias);
            }
        }

        return $this;
    }

    /**
     * Find column index for given alias
     *
     * @param string $alias
     *
     * @return string
     */
    private function findColumnIndex($alias) : int
    {
        foreach ($this->columns as $index => $data) {
            if ($data[1] === $alias) {
                return $index;
            }
        }
    }

    /**
     * Remove column from projection
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeColumn(string $alias)
    {
        $index = $this->findColumnIndex($alias);

        if (null !== $index) {
            unset($this->columns[$index]);
        }

        return $this;
    }

    /**
     * Does this project have the given column
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasColumn(string $alias) : bool
    {
        return (bool)$this->findColumnIndex($alias);
    }

    /**
     * Get group by clauses array
     *
     * @return array
     */
    public function getAllGroupBy() : array
    {
        return $this->groups;
    }

    /**
     * Get order by clauses array
     *
     * @return array
     */
    public function getAllOrderBy() : array
    {
        return $this->orders;
    }

    /**
     * Get query range
     *
     * @return int[]
     *   First value is limit second is offset
     */
    public function getRange() : array
    {
        return [$this->limit, $this->offset];
    }

    /**
     * Add a condition in the where clause
     *
     * @param string|ExpressionColumn $column
     * @param mixed $value
     * @param string $operator
     *
     * @return $this
     */
    public function condition($column, $value, string $operator = Where::EQUAL)
    {
        $this->where->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the where clause
     *
     * @param string|Expression $statement
     *   SQL string, which may contain parameters
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     *
     * @return $this
     */
    public function expression($statement, $arguments = [])
    {
        $this->where->expression($statement, $arguments);

        return $this;
    }

    /**
     * Add a condition in the having clause
     *
     * @param string|ExpressionColumn $column
     * @param mixed $value
     * @param string $operator
     *
     * @return $this
     */
    public function havingCondition($column, $value, string $operator = Where::EQUAL)
    {
        $this->having->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the having clause
     *
     * @param string|Expression $statement
     *   SQL string, which may contain parameters
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     *
     * @return $this
     */
    public function havingExpression($statement, $arguments = [])
    {
        $this->having->expression($statement, $arguments);

        return $this;
    }

    /**
     * Get where statement
     *
     * @return Where
     */
    public function getWhere() : Where
    {
        return $this->where;
    }

    /**
     * Get having statement
     *
     * @return Where
     */
    public function getHaving() : Where
    {
        return $this->having;
    }

    /**
     * Add an order by clause
     *
     * @param string|Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement
     * @param int $order
     *   One of the Query::ORDER_* constants
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default
     *
     * @return $this
     */
    public function orderBy($column, int $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE)
    {
        if (!$column instanceof Expression) {
            $column = new ExpressionColumn($column);
        }

        $this->orders[] = [$column, $order, $null];

        return $this;
    }

    /**
     * Add an order by clause as a raw SQL expression
     *
     * @param string|Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement
     * @param int $order
     *   One of the Query::ORDER_* constants
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default
     *
     * @return $this
     */
    public function orderByExpression($column, int $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE)
    {
        if (!$column instanceof Expression) {
            $column = new ExpressionRaw($column);
        }

        $this->orders[] = [$column, $order, $null];

        return $this;
    }

    /**
     * Add a group by clause
     *
     * @param string|ExpressionColumn $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement
     *
     * @return $this
     */
    public function groupBy($column)
    {
        if (!is_string($column) && !$column instanceof ExpressionColumn) {
            throw new QueryError("grouping by something else than a column name is not supported");
        }

        if (is_string($column)) {
            $column = new ExpressionColumn($column);
        }

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
    public function range(int $limit = 0, int $offset = 0)
    {
        if (!is_int($limit) || $limit < 0) {
            throw new QueryError(sprintf("limit must be a positive integer: '%s' given", $limit));
        }
        if (!is_int($offset) || $offset < 0) {
            throw new QueryError(sprintf("offset must be a positive integer: '%s' given", $offset));
        }

        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        $arguments = new ArgumentBag();

        // SELECT
        foreach ($this->columns as $column) {
            if ($column[0] instanceof ArgumentHolderInterface) {
                $arguments->append($column[0]->getArguments());
            }
        }

        // JOIN
        foreach ($this->joins as $join) {
            if ($join[0] instanceof ArgumentHolderInterface) {
                $arguments->append($join[0]->getArguments());
            }
            if ($join[1] instanceof ArgumentHolderInterface) {
                $arguments->append($join[1]->getArguments());
            }
        }

        // WHERE
        if (!$this->where->isEmpty()) {
            $arguments->append($this->where->getArguments());
        }

        // GROUP BY
        foreach ($this->orders as $order) {
            if ($order[0] instanceof ArgumentHolderInterface) {
                $arguments->append($order[0]->getArguments());
            }
        }

        // HAVING
        if (!$this->having->isEmpty()) {
            $arguments->append($this->having->getArguments());
        }

        return $arguments;
    }

    /**
     * Get the count SelectQuery
     *
     * @param string $countAlias
     *   Alias of the count column
     *
     * @return SelectQuery
     *   Returned query will be a clone, the count row will be aliased with the
     *   given alias
     */
    public function getCountQuery(string $countAlias = 'count') : SelectQuery
    {
        // @todo do not remove necessary fields for group by and other
        //   aggregates functions (SQL standard)
        return (clone $this)
            ->removeAllColumns()
            ->removeAllOrder()
            ->range(0, 0)
            ->column(new ExpressionRaw("count(*)"), $countAlias)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function willReturnRows() : bool
    {
        return !$this->performOnly;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneJoins();

        foreach ($this->columns as $index => $column) {
            $this->columns[$index][0] = clone $column[0];
        }

        foreach ($this->orders as $index => $order) {
            $this->orders[$index][0] = clone $order[0];
        }

        $this->where = clone $this->where;
        $this->having = clone $this->having;
    }
}
