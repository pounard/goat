<?php

namespace Goat\Core\Query\Partial;

use Goat\Core\Error\QueryError;

/**
 * Aliasing and conflict dedupe logic.
 */
trait AliasHolderTrait
{
    private $aliasIndex = 0;
    private $relations = [];

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
     * @return boolean
     */
    protected function aliasExists($alias)
    {
        return isset($this->relations[$alias]);
    }
}
