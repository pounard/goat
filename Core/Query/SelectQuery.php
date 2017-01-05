<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ConnectionInterface;

/**
 * Represents a select query
 *
 * @todo this needs to be plugged to an escaper, for literal escaping such as
 *   column names and relation names
 */
class SelectQuery
{
    const ALIAS_PREFIX = 'goat';

    const JOIN_NATURAL = 1;
    const JOIN_LEFT = 2;
    const JOIN_LEFT_OUTER = 3;
    const JOIN_INNER = 4;

    const ORDER_ASC = 1;
    const ORDER_DESC = 2;
    const NULL_IGNORE = 0;
    const NULL_LAST = 1;
    const NULL_FIRST = 2;

    private $aliasIndex = 0;
    private $fields = [];
    private $relation;
    private $relationAlias;
    private $relations = [];
    private $joins = [];
    private $sql;
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
        if (null === $alias) {
            $alias = $relation;
        }

        $this->relation = $relation;
        $this->relations[$alias] = $relation;
        $this->relationAlias = $alias;
        $this->where = new Where();
        $this->having = new Where();
    }

    /**
     * Get SQL from relation
     *
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * Proxy of ::getAliasFor(::getRelation())
     *
     * @return string
     */
    public function getRelationAlias()
    {
        return $this->relationAlias;
    }

    /**
     * Get alias for relation, if none registered add a new one
     *
     * @param string $relation
     *
     * @return string
     */
    public function getAliasFor($relation)
    {
        $index = array_search($relation, $this->relations);

        if (false !== $index) {
            $alias = self::ALIAS_PREFIX . ++$this->aliasIndex;
        } else {
            $alias = $relation;
        }

        $this->relations[$alias] = $relation;

        return $alias;
    }

    /**
     * Get select columns array
     *
     * @return array
     */
    public function getAllColumns()
    {
        return $this->fields;
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
     * Get join clauses array
     *
     * @return array
     */
    public function getAllJoin()
    {
        return $this->joins;
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
     * Does alias exists
     *
     * @param string $alias
     *
     * @return boolean
     */
    public function aliasExists($alias)
    {
        return isset($this->relations[$alias]);
    }

    /**
     * Set or replace a field with a content.
     *
     * @param string $statement
     *   SQL select field
     * @param string
     *   If alias to be different from the field
     *
     * @return $this
     */
    public function field($statement, $alias = null)
    {
        $noAlias = false;

        if (!$alias) {
            if (!is_string($statement)) {
                throw new \InvalidArgumentException("when providing no alias for select field, statement must be a string");
            }

            // Match for RELATION.COLUMN for aliasing properly
            if (false !==  strpos($statement, '.')) {
                list(, $column) = explode('.', $statement);

                if ('*' === $column) {
                    $alias = $statement;
                    $noAlias = true;
                } else {
                    $alias = $column;
                }

            } else {
                $alias = $statement;
            }
        }

        $this->fields[$alias] = [$statement, ($noAlias ? null : $alias)];

        return $this;
    }

    /**
     * Remove field from projection
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeField($alias)
    {
        unset($this->fields[$alias]);

        return $this;
    }

    /**
     * Does this project have the given field
     *
     * @param string $name
     *
     * @return boolean
     */
    public function hasField($alias)
    {
        return isset($this->fields[$alias]);
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
     * Add join statement
     *
     * @param string $relation
     * @param string|Where|RawStatement $condition
     * @param string $alias
     * @param int $mode
     *
     * @return $this
     */
    public function join($relation, $condition = null, $alias = null, $mode = self::JOIN_INNER)
    {
        if (null === $alias) {
            $alias = $this->getAliasFor($relation);
        } else {
            if ($this->aliasExists($alias)) {
                throw new \InvalidArgumentException(sprintf("%s alias is already registered for relation %s", $alias, $this->relations[$alias]));
            }
        }

        if (null === $condition) {
            $condition = new Where();
        } else if (is_string($condition) || $condition instanceof RawStatement) {
            $condition = (new Where())->statement($condition);
        } else {
            if (!$condition instanceof Where) {
                throw new \InvalidArgumentException(sprintf("condition must be either a string or an instance of %s", Where::class));
            }
        }

        $this->joins[$alias] = [$relation, $condition, $mode];

        return $this;
    }

    /**
     * Add join statement and return the associated Where
     *
     * @param string $relation
     * @param string $alias
     * @param int $mode
     *
     * @return Where
     */
    public function joinWhere($relation, $alias = null, $mode = self::JOIN_INNER)
    {
        if (null === $alias) {
            $alias = $this->getAliasFor($relation);
        } else {
            if ($this->aliasExists($alias)) {
                throw new \InvalidArgumentException(sprintf("%s alias is already registered for relation %s", $alias, $this->relations[$alias]));
            }
        }

        $this->joins[$alias] = [$relation, $condition = new Where(), $mode];

        return $condition;
    }

    /**
     * Add inner statement
     *
     * @param string $relation
     * @param string|Where $condition
     * @param string $alias
     *
     * @return $this
     */
    public function innerJoin($relation, $condition = null, $alias = null)
    {
        $this->join($relation, $condition, $alias, self::JOIN_INNER);

        return $this;
    }

    /**
     * Add left outer join statement
     *
     * @param string $relation
     * @param string|Where $condition
     * @param string $alias
     *
     * @return $this
     */
    public function leftJoin($relation, $condition = null, $alias = null)
    {
        $this->join($relation, $condition, $alias, self::JOIN_LEFT_OUTER);

        return $this;
    }

    /**
     * Add inner statement and return the associated Where
     *
     * @param string $relation
     * @param string $alias
     *
     * @return $this
     */
    public function innerJoinWhere($relation, $alias = null)
    {
        return $this->joinWhere($relation, $alias, self::JOIN_INNER);
    }

    /**
     * Add left outer join statement and return the associated Where
     *
     * @param string $relation
     * @param string $alias
     *
     * @return $this
     */
    public function leftJoinWhere($relation, $alias = null)
    {
        return $this->joinWhere($relation, $alias, self::JOIN_LEFT_OUTER);
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
     *   One of the SelectQuery::ORDER_* constants
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default
     *
     * @return $this
     */
    public function orderBy($column, $order = self::ORDER_ASC, $null = self::NULL_IGNORE)
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
            throw new \InvalidArgumentException("limit must be a positive integer");
        }
        if (!is_int($offset) || $offset < 0) {
            throw new \InvalidArgumentException("offset must be a positive integer");
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
