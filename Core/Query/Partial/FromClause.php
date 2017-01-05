<?php

namespace Goat\Core\Query\Partial;

use Goat\Core\Error\QueryError;
use Goat\Core\Query\Query;
use Goat\Core\Query\Where;

/**
 * Represents the FROM part of a DELETE, SELECT or UPDATE query.
 *
 * It gathers all the FROM and JOIN statements altogether.
 */
class FromClause extends SelectClause
{
    private $joins = [];

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
     * Add join statement
     *
     * @param string $relation
     * @param string|Where|RawStatement $condition
     * @param string $alias
     * @param int $mode
     *
     * @return $this
     */
    public function join($relation, $condition = null, $alias = null, $mode = Query::JOIN_INNER)
    {
        if (null === $alias) {
            $alias = $this->getAliasFor($relation);
        } else {
            if ($this->aliasExists($alias)) {
                throw new QueryError(sprintf("%s alias is already registered for relation %s", $alias, $this->relations[$alias]));
            }
        }

        if (null === $condition) {
            $condition = new Where();
        } else if (is_string($condition) || $condition instanceof RawStatement) {
            $condition = (new Where())->statement($condition);
        } else {
            if (!$condition instanceof Where) {
                throw new QueryError(sprintf("condition must be either a string or an instance of %s", Where::class));
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
    public function joinWhere($relation, $alias = null, $mode = Query::JOIN_INNER)
    {
        if (null === $alias) {
            $alias = $this->getAliasFor($relation);
        } else {
            if ($this->aliasExists($alias)) {
                throw new QueryError(sprintf("%s alias is already registered for relation %s", $alias, $this->relations[$alias]));
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
        $this->join($relation, $condition, $alias, Query::JOIN_INNER);

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
        $this->join($relation, $condition, $alias, Query::JOIN_LEFT_OUTER);

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
        return $this->joinWhere($relation, $alias, Query::JOIN_INNER);
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
        return $this->joinWhere($relation, $alias, Query::JOIN_LEFT_OUTER);
    }
}
