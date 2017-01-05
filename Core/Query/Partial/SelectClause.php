<?php

namespace Goat\Core\Query\Partial;

use Goat\Core\Error\QueryError;

/**
 * Represents the SELECT part of a SELECT query.
 *
 * It can be used for RETURNING statements.
 *
 * @todo
 *   - support a SelectQuery as FROM relation
 *   - implement __clone() once this done
 */
class SelectClause
{
    const ALIAS_PREFIX = 'goat';

    private $aliasIndex = 0;
    private $columns = [];
    private $relation;
    private $relationAlias;
    private $relations = [];

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
     * Get select columns array
     *
     * @return array
     */
    public function getAllColumns()
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
     * Set or replace a column with a content.
     *
     * @param string $statement
     *   SQL select column
     * @param string
     *   If alias to be different from the column
     *
     * @return $this
     */
    public function column($statement, $alias = null)
    {
        $noAlias = false;

        if (!$alias) {
            if (!is_string($statement)) {
                throw new QueryError("when providing no alias for select column, statement must be a string");
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

        $this->columns[$alias] = [$statement, ($noAlias ? null : $alias)];

        return $this;
    }

    /**
     * Remove column from projection
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeColumn($alias)
    {
        unset($this->columns[$alias]);

        return $this;
    }

    /**
     * Does this project have the given column
     *
     * @param string $name
     *
     * @return boolean
     */
    public function hasColumn($alias)
    {
        return isset($this->columns[$alias]);
    }
}
