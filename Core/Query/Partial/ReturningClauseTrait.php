<?php

namespace Goat\Core\Query\Partial;

use Goat\Core\Error\QueryError;
use Goat\Core\Query\Statement;

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
     * @param string $statement
     *   SQL select column
     * @param string
     *   If alias to be different from the column
     *
     * @return $this
     */
    public function returning($statement, $alias = null)
    {
        $noAlias = false;

        if (!$alias) {
            if (!is_string($statement) && !$statement instanceof Statement) {
                throw new QueryError("RETURNING values can only be column names or expressions using them from the previous statement");
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

        $this->return[$alias] = [$statement, ($noAlias ? null : $alias)];

        return $this;
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
        unset($this->return[$alias]);

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
        return isset($this->return[$alias]);
    }

    /**
     * {@inheritdoc}
     */
    public function willReturnRows()
    {
        return !empty($this->return);
    }
}
