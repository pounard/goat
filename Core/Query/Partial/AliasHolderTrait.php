<?php

namespace Goat\Core\Query\Partial;

use Goat\Core\Error\QueryError;
use Goat\Core\Query\ExpressionRelation;

/**
 * Aliasing and conflict dedupe logic.
 */
trait AliasHolderTrait
{
    private $aliasIndex = 0;
    private $relations = [];

    /**
     * Normalize relation reference
     *
     * @param string|ExpressionRelation $relation
     * @param string $alias
     *
     * @throws QueryError
     */
    protected function normalizeRelation($relation, $alias)
    {
        if ($relation instanceof ExpressionRelation) {
            if ($relation->getAlias() && $alias) {
                throw new QueryError(sprintf(
                    "relation %s is already prefixed by %s, conflicts with %s",
                    $relation->getRelation(),
                    $relation->getAlias(),
                    $alias
                ));
            }
        } else {
            if (null === $alias) {
                $alias = $this->getAliasFor($relation);
            } else {
                if ($this->aliasExists($alias)) {
                    throw new QueryError(sprintf("%s alias is already registered for relation %s", $alias, $this->relations[$alias]));
                }
            }

            $relation = new ExpressionRelation($relation, $alias);
        }

        return $relation;
    }

    /**
     * Get alias for relation, if none registered add a new one
     *
     * @param string $relation
     * @param string $userAlias
     *   Existing alias if any
     *
     * @throws QueryError
     *   If an alias is given and it already exists
     *
     * @return string
     */
    protected function getAliasFor($relation, $userAlias = null)
    {
        if ($userAlias) {
            if (isset($this->relations[$userAlias])) {
                throw new QueryError(
                    sprintf(
                        "cannot use alias %s for relation %s, already in use for table %s",
                        $userAlias,
                        $relation,
                        $this->relations[$userAlias]
                    )
                );
            } else {
                $this->relations[$userAlias] = $relation;

                return $userAlias;
            }
        }

        $index = array_search($relation, $this->relations);

        if (false !== $index) {
            $alias = 'goat_' . ++$this->aliasIndex;
        } else {
            $alias = $relation;
        }

        $this->relations[$alias] = $relation;

        return $alias;
    }

    /**
     * Remove alias
     *
     * @param string $alias
     */
    protected function removeAlias($alias)
    {
        unset($this->relations[$alias]);
    }

    /**
     * Does alias exists
     *
     * @param string $alias
     *
     * @return bool
     */
    protected function aliasExists($alias)
    {
        return isset($this->relations[$alias]);
    }
}
