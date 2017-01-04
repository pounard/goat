<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ConnectionInterface;

/**
 * Represents a paginated query
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

    use SelectTrait;

    private $fields = [];
    private $aliasIndex = 0;
    private $relation;
    private $relationAlias;
    private $relations = [];
    private $joins = [];
    private $sql;
    private $where;
    private $having;
    private $groups = [];
    private $orders = [];
    private $range;

    /**
     * Build a new query
     *
     * @param string $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     * @param ConnectionInterface $connection
     *   Connection, so that it can really do query
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
     * Add join statement
     *
     * @param string $relation
     * @param string|Where $condition
     * @param string $alias
     * @param int $mode
     *
     * @return Where
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
        } else if (is_string($condition)) {
            $condition = (new Where())->statement($condition);
        } else {
            if (!$condition instanceof Where) {
                throw new \InvalidArgumentException(sprintf("condition must be either a string or an instance of %s", Where::class));
            }
        }

        $this->joins[$alias] = [$relation, $condition, $mode];

        return $condition;
    }

    /**
     * Add inner statement
     *
     * @param string $relation
     * @param string|Where $condition
     * @param string $alias
     *
     * @return Where
     */
    public function innerJoin($relation, $condition = null, $alias = null)
    {
        return $this->join($relation, $condition, $alias, self::JOIN_INNER);
    }

    /**
     * Add left outer join statement
     *
     * @param string $relation
     * @param string|Where $condition
     * @param string $alias
     *
     * @return Where
     */
    public function leftJoin($relation, $condition = null, $alias = null)
    {
        return $this->join($relation, $condition, $alias, self::JOIN_LEFT_OUTER);
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
        $this->range = [$limit, $offset];

        return $this;
    }

    /**
     * Format a single group by
     *
     * @param string $column
     * @param int $order
     * @param int $null
     */
    protected function formatOrderBy($column, $order, $null)
    {
        if (self::ORDER_ASC === $order) {
            $orderStr = 'asc';
        } else {
            $orderStr = 'desc';
        }

        switch ($null) {

            case self::NULL_FIRST:
                $nullStr = ' nulls first';
                break;

            case self::NULL_LAST:
                $nullStr = ' nulls last';
                break;

            case self::NULL_IGNORE:
            default:
                $nullStr = '';
                break;
        }

        return sprintf('%s %s%s', $column, $orderStr, $nullStr);
    }

    /**
     * Format all single order by clause
     *
     * @return string
     */
    private function formatOrderByAll()
    {
        if (!$this->orders) {
            return '';
        }

        $output = [];

        foreach ($this->orders as $data) {
            list($column, $order, $null) = $data;
            $output[] = $this->formatOrderBy($column, $order, $null);
        }

        return "order by " . implode(", ", $output);
    }

    /**
     * Format a single group by clause
     *
     * @return string
     */
    protected function formatGroupBy($column)
    {
        return $column;
    }

    /**
     * Format all single group by clause
     */
    private function formatGroupByAll()
    {
        if (!$this->groups) {
            return '';
        }

        $output = [];

        foreach ($this->groups as $column) {
            $output[] = $this->formatGroupBy($column);
        }

        return "group by " . implode(", ", $output);
    }

    /**
     * Format a single join statement
     *
     * @param int $mode
     * @param string $relation
     * @param string $alias
     * @param Where $condition
     *
     * @return string
     */
    protected function formatJoin($mode, $relation, $alias, Where $condition)
    {
        switch ($mode) {

            case self::JOIN_NATURAL:
                $prefix = 'natural join';
                break;

            case self::JOIN_LEFT:
            case self::JOIN_LEFT_OUTER:
                $prefix = 'left outer join';
                break;

            case self::JOIN_INNER:
            default:
                $prefix = 'inner join';
                break;
        }

        if ($condition->isEmpty()) {
            return sprintf("%s %s %s", $prefix, $relation, $alias);
        } else {
            return sprintf("%s %s %s on (%s)", $prefix, $relation, $alias, $condition);
        }
    }

    /**
     * Format all join statements
     *
     * @return string
     */
    private function formatJoinAll()
    {
        if (!$this->joins) {
            return '';
        }

        $output = [];

        foreach ($this->joins as $alias => $join) {
            list($relation, $condition, $mode) = $join;
            $output[] = $this->formatJoin($mode, $relation, $alias, $condition);
        }

        return implode("\n", $output);
    }

    /**
     * Format range statement
     *
     * @param int $limit
     *   O means no limit
     * @param int $offset
     *   0 means default offset
     */
    protected function formatRange($limit = 0, $offset = 0)
    {
        if ($limit) {
            return sprintf('limit %d offset %d', $limit, $offset);
        } else if ($offset) {
            return sprintf('offset %d', $offset);
        } else {
            return '';
        }
    }

    /**
     * Format current range statement
     *
     * @return string
     */
    private function formatRangeAll()
    {
        if (!$this->range) {
            return '';
        }

        return $this->formatRange($this->range[0], $this->range[1]);
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

    /**
     * From query as SQL
     *
     * @return string
     */
    public function format()
    {
        $output = [];
        $output[] = sprintf(
            "select %s\nfrom %s %s\n%s",
            $this->formatProjectionAll(),
            $this->relation,
            $this->relationAlias,
            $this->formatJoinAll()
        );

        if (!$this->where->isEmpty()) {
            $output[] = sprintf('where %s', $this->where);
        }
        if ($this->groups) {
            $output[] = $this->formatGroupByAll();
        }
        if ($this->orders) {
            $output[] = $this->formatOrderByAll();
        }
        if ($this->range) {
            $output[] = $this->formatRangeAll();
        }
        if (!$this->having->isEmpty()) {
            $output[] = sprintf('having %s', $this->having);
        }

        return implode("\n", $output);
    }
}
