<?php

namespace Goat\Core\Query;

/**
 * Represents a raw value
 */
class ExpressionRelation implements ExpressionInterface
{
    private $alias;
    private $relation;
    private $schema;

    /**
     * Default constructor
     *
     * @param string $column
     * @param string $relation
     */
    public function __construct($relation, $alias = null, $schema = null)
    {
        if (null === $schema) {
            if (false !== strpos($relation, '.')) {
                list($schema, $relation) = explode('.', $relation, 2);
            }
        }

        $this->relation = $relation;
        $this->alias = $alias;
        $this->schema = $schema;
    }

    /**
     * Get relation
     *
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * Get alias
     *
     * @return null|string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Get schema
     *
     * @return null|string
     */
    public function getSchema()
    {
        return $this->schema;
    }
}
