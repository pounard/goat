<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\SelectQuery;

/**
 * Represents the WITH part of any query.
 */
trait WithClauseTrait
{
    private $withs = [];

    /**
     * Get with clauses array
     *
     * @return array
     */
    final public function getAllWith() : array
    {
        return $this->withs;
    }

    /**
     * Add with statement
     *
     * @return $this
     */
    final public function with(string $alias, SelectQuery $select, bool $isRecursive = false)
    {
        $this->withs[] = [$alias, $select, $isRecursive];

        return $this;
    }

    /**
     * Create new with statement
     *
     * @param string $alias
     *    Alias for the with clause
     * @param string|\Goat\Query\ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $relationAlias
     *   Alias for from clause relation of the select query
     * @param bool $isRecursive
     *
     * @return SelectQuery
     */
    final public function createWith(string $alias, $relation, string $relationAlias = null, bool $isRecursive = false) : SelectQuery
    {
        if ($this->runner) {
            $select = $this->runner->select($relation, $relationAlias);
        } else {
            $select = new SelectQuery($relation, $relationAlias);
        }

        $this->with($alias, $select, $isRecursive);

        return $select;
    }

    /**
     * Deep clone support.
     */
    protected function cloneJoins()
    {
        foreach ($this->withs as $index => $with) {
            $this->withs[$index][1] = clone $with[1];
        }
    }
}
