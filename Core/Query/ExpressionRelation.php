<?php

declare(strict_types=1);

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;

/**
 * Represents a raw value
 */
final class ExpressionRelation implements Expression
{
    /**
     * Creates an instance without automatic split using '.' notation
     *
     * @param string $relationName
     * @param string $alias
     * @param string $schema
     *
     * @return ExpressionRelation
     */
    public static function escape(string $relationName, string $alias = null, string $schema = null) : ExpressionRelation
    {
        $instance = new ExpressionRelation('');
        $instance->relationName = $relationName;
        $instance->alias = $alias;
        $instance->schema = $schema;

        return $instance;
    }

    private $alias;
    private $relationName;
    private $schema;

    /**
     * Default constructor
     *
     * @param string $relationName
     * @param string $alias
     * @param string $schema
     */
    public function __construct(string $relationName, string $alias = null, string $schema = null)
    {
        if (null === $schema) {
            if (false !== strpos($relationName, '.')) {
                list($schema, $relationName) = explode('.', $relationName, 2);
            }
        }

        $this->relationName = $relationName;
        $this->alias = $alias;
        $this->schema = $schema;
    }

    /**
     * Get relation
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->relationName;
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
