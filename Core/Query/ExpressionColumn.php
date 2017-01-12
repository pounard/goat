<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;

/**
 * Represents a raw value
 */
final class ExpressionColumn implements Expression
{
    private $column;
    private $relation;

    /**
     * Default constructor
     *
     * @param string $column
     * @param string $relation
     */
    public function __construct(string $column, string $relation = null)
    {
        if (null === $relation) {
            if (false !== strpos($column, '.')) {
                list($relation, $column) = explode('.', $column, 2);
            }
        }

        $this->column = $column;
        $this->relation = $relation;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getColumn() : string
    {
        return $this->column;
    }

    /**
     * Get value type
     *
     * @return null|string
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        return new ArgumentBag();
    }
}
