<?php

namespace Goat\Core\Query\Partial;

use Goat\Core\Error\QueryError;

/**
 * Represents the SELECT part of a SELECT query.
 *
 * It can be used for RETURNING statements.
 */
class SelectClause
{
    const ALIAS_PREFIX = 'goat';

    private $aliasIndex = 0;
    private $fields = [];
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
                throw new QueryError("when providing no alias for select field, statement must be a string");
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
}
