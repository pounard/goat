<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;

/**
 * Represents a raw value
 */
final class ExpressionRelation implements Expression
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
    public function __construct(string $relation, string $alias = null, string $schema = null)
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
    public function getRelation() : string
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

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        return new ArgumentBag();
    }
}
