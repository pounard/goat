<?php

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
    public function getAllReturn()
    {
        return $this->return;
    }

    /**
     * Remove everything from the current SELECT clause
     *
     * @return $this
     */
    public function removeAllReturn()
    {
        $this->return = [];

        return $this;
    }

    /**
     * Set or replace a column with a content.
     *
     * @param string $expression
     *   SQL select column
     * @param string
     *   If alias to be different from the column
     *
     * @return $this
     */
    public function returning($expression, $alias = null)
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
     */
    private function findReturnIndex($alias)
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
    public function removeReturn($alias)
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
     * @return boolean
     */
    public function hasReturn($alias)
    {
        return (bool)$this->findReturnIndex($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function willReturnRows()
    {
        return !empty($this->return);
    }
}
