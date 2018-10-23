<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Represents a raw value
 */
final class ExpressionRelation implements Expression
{
    /**
     * @var Relation
     */
    private $relation;
    private $alias;

    /**
     * Default constructor
     */
    private function __construct()
    {
    }

    /**
     * Creates an instance without automatic split using '.' notation
     */
    public static function escape(string $relationName, string $alias = null, string $schema = null) : self
    {
        $ret = new self;
        $ret->relation = Relation::escape($relationName, $schema);
        $ret->alias = $alias;

        return $ret;
    }

    /**
     * Create instance from arbitrary input value
     */
    public static function from($relation): self
    {
        if (!$relation instanceof ExpressionRelation) {
            $relation = self::create($relation);
        }

        return $relation;
    }

    /**
     * Create instance from name and alias
     */
    public static function create(string $relationName, string $alias = null, string $schema = null): self
    {
        $ret = new self;
        $ret->relation = Relation::create($relationName, $schema);
        $ret->alias = $alias;

        return $ret;
    }

    /**
     * Get relation
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->relation->getName();
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
        return $this->relation->getSchema();
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        return new ArgumentBag();
    }
}
