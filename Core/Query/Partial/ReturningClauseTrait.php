<?php

declare(strict_types=1);

namespace Goat\Core\Query\Partial;

use Goat\Core\Error\QueryError;
use Goat\Core\Query\Expression;
use Goat\Core\Query\ExpressionColumn;

/**
 * Represents the RETURNING part of any query.
 */
trait ReturningClauseTrait
{
    private $return = [];

    /**
     * Get select columns array
     *
     * @return string[][]
     *   Values are arrays which contain:
     *     - first value: the column identifier (may contain the table alias
     *       or name with dot notation)
     *     - second value: the alias if any, or null
     */
    public function getAllReturn() : array
    {
        return $this->return;
    }

    /**
     * Remove everything from the current SELECT clause
     *
     * @return $this
     */
    public function removeAllReturn() : array
    {
        $this->return = [];

        return $this;
    }

    /**
     * Set or replace a column with a content.
     *
     * @param string|Expression $expression
     *   SQL select column
     * @param string $alias
     *   If alias to be different from the column
     *
     * @return $this
     */
    public function returning($expression, string $alias = null)
    {
        if (!$alias) {
            if (!is_string($expression) && !$expression instanceof Expression) {
                throw new QueryError("RETURNING values can only be column names or expressions using them from the previous statement");
            }
            if (is_string($expression)) {
                $expression = new ExpressionColumn($expression);
            }
        }

        $this->return[] = [$expression, $alias];

        return $this;
    }

    /**
     * Find column index for given alias
     *
     * @param string $alias
     *
     * @return null|string
     */
    private function findReturnIndex(string $alias) : string
    {
        foreach ($this->return as $index => $data) {
            if ($data[1] === $alias) {
                return $index;
            }
        }
    }

    /**
     * Remove column from projection
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeReturn(string $alias)
    {
        $index = $this->findReturnIndex($alias);

        if (null !== $index) {
            unset($this->return[$index]);
        }

        return $this;
    }

    /**
     * Does this project have the given column
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasReturn(string $alias) : bool
    {
        return (bool)$this->findReturnIndex($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function willReturnRows() : bool
    {
        return !empty($this->return);
    }
}
