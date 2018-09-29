<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Error\QueryError;
use Goat\Query\Expression;
use Goat\Query\ExpressionColumn;

/**
 * Represents the RETURNING part of any query.
 */
trait ReturningQueryTrait /* implements ReturningQueryInterface */
{
    private $return = [];

    /**
     * {@inheritdoc}
     */
    public function getAllReturn() : array
    {
        return $this->return;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllReturn() : array
    {
        $this->return = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function returning($expression, string $alias = null)
    {
        if (!$alias) {
            if (!\is_string($expression) && !$expression instanceof Expression) {
                throw new QueryError("RETURNING values can only be column names or expressions using them from the previous statement");
            }
            if (\is_string($expression)) {
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
