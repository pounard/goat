<?php

namespace Goat\Core\Query\Partial;

use Goat\Core\Error\QueryError;
use Goat\Core\Query\ExpressionRaw;
use Goat\Core\Query\Query;
use Goat\Core\Query\Where;

/**
 * Represents the FROM part of a DELETE, SELECT or UPDATE query.
 *
 * It gathers all the FROM and JOIN statements altogether.
 */
trait FromClauseTrait
{
    use AliasHolderTrait;

    private $joins = [];

    /**
     * Get join clauses array
     *
     * @return array
     */
    final public function getAllJoin()
    {
        return $this->joins;
    }

    /**
     * Add join statement
     *
     * @param string $relation
     * @param string|Where|ExpressionRaw $condition
     * @param string $alias
     * @param int $mode
     *
     * @return $this
     */
    final public function join($relation, $condition = null, $alias = null, $mode = Query::JOIN_INNER)
    {
        $relation = $this->normalizeRelation($relation, $alias);

        if (null === $condition) {
            $condition = new Where();
        } else if (is_string($condition) || $condition instanceof ExpressionRaw) {
            $condition = (new Where())->expression($condition);
        } else {
            if (!$condition instanceof Where) {
                throw new QueryError(sprintf("condition must be either a string or an instance of %s", Where::class));
            }
        }

        $this->joins[] = [$relation, $condition, $mode];

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
    final public function joinWhere($relation, $alias = null, $mode = Query::JOIN_INNER)
    {
        $relation = $this->normalizeRelation($relation, $alias);

        $this->joins[] = [$relation, $condition = new Where(), $mode];

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
    final public function innerJoin($relation, $condition = null, $alias = null)
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
    final public function leftJoin($relation, $condition = null, $alias = null)
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
    final public function innerJoinWhere($relation, $alias = null)
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
    final public function leftJoinWhere($relation, $alias = null)
    {
        return $this->joinWhere($relation, $alias, Query::JOIN_LEFT_OUTER);
    }

    /**
     * Deep clone support.
     */
    protected function cloneJoins()
    {
        foreach ($this->joins as $index => $join) {
            $this->joins[$index][0] = clone $join[0];
            $this->joins[$index][1] = clone $join[1];
        }
    }
}
