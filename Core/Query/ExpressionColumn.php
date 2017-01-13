<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;

/**
 * Represents a raw value
 */
final class ExpressionColumn implements Expression
{
    private $columnName;
    private $relationAlias;

    /**
     * Default constructor
     *
     * @param string $columnName
     * @param string $relationAlias
     */
    public function __construct(string $columnName, string $relationAlias = null)
    {
        if (null === $relationAlias) {
            if (false !== strpos($columnName, '.')) {
                list($relationAlias, $columnName) = explode('.', $columnName, 2);
            }
        }

        $this->columnName = $columnName;
        $this->relationAlias = $relationAlias;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->columnName;
    }

    /**
     * Get value type
     *
     * @return null|string
     */
    public function getRelationAlias()
    {
        return $this->relationAlias;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        return new ArgumentBag();
    }
}
