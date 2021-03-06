<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Error\QueryError;
use Goat\Query\Partial\AbstractQuery;
use Goat\Query\Partial\ReturningQueryTrait;

/**
 * Represents an INSERT VALUES query
 */
final class InsertValuesQuery extends AbstractQuery implements ReturningQueryInterface
{
    use ReturningQueryTrait;

    private $arguments;
    private $columns = [];
    private $valueCount = 0;

    /**
     * Build a new query
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     */
    public function __construct($relation)
    {
        // INSERT queries main relation cannot be aliased
        parent::__construct($relation);

        $this->arguments = new ArgumentBag();
    }

    /**
     * Get select columns array
     *
     * @return string[]
     */
    public function getAllColumns() : array
    {
        return $this->columns;
    }

    /**
     * Add columns
     *
     * @param string[] $columns
     *   List of columns names
     *
     * @return $this
     */
    public function columns(array $columns)
    {
        if ($this->valueCount) {
            throw new QueryError("once you added value, you cannot change columns anymore");
        }

        $this->columns = \array_unique(\array_merge($this->columns, $columns));

        return $this;
    }

    /**
     * Get all values
     *
     * @return int
     */
    public function getValueCount() : int
    {
        return $this->valueCount;
    }

    /**
     * Add a set of values
     *
     * @param array $values
     *   Either values are numerically indexed, case in which they must match
     *   the internal columns order, or they can be key-value pairs case in
     *   which matching will be dynamically be done
     *
     * @todo
     *   - implement it correctly
     *   - allow arbitrary statement or subqueries?
     *
     * @return $this
     */
    public function values(array $values)
    {
        if (!$this->columns) {
            $this->columns = \array_keys($values);
        }

        if (\count($values) !== \count($this->columns)) {
            throw new QueryError("values count does not match column count");
        }

        foreach ($values as $value) {
            $this->arguments->add($value);
        }

        $this->valueCount++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        return $this->arguments;
    }
}
