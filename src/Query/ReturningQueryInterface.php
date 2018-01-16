<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Any query that can return something
 */
interface ReturningQueryInterface
{
    /**
     * Get select columns array
     *
     * @return string[][]
     *   Values are arrays which contain:
     *     - first value: the column identifier (may contain the table alias
     *       or name with dot notation)
     *     - second value: the alias if any, or null
     */
    public function getAllReturn() : array;

    /**
     * Remove everything from the current SELECT clause
     *
     * @return $this
     */
    public function removeAllReturn() : array;

    /**
     * Set or replace a column with a content.
     *
     * @param string|\Goat\Query\Expression $expression
     *   SQL select column
     * @param string $alias
     *   If alias to be different from the column
     *
     * @return $this
     */
    public function returning($expression, string $alias = null);

    /**
     * Remove column from projection
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeReturn(string $alias);

    /**
     * Does this project have the given column
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasReturn(string $alias) : bool;
}
