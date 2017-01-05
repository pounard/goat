<?php

namespace Goat\Core\Query;

use Goat\Core\Error\QueryError;

/**
 * Represents an INSERT VALUES query
 */
class InsertValuesQuery implements Query
{
    use QueryTrait;

    private $relation;
    private $columns = [];
    private $valueCount = 0;
    private $arguments = [];

    /**
     * Build a new query
     *
     * @param string $relation
     *   SQL from statement relation name
     */
    public function __construct($relation)
    {
        $this->relation = $relation;
    }

    /**
     * Get SQL from relation
     *
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * Get select columns array
     *
     * @return string
     */
    public function getAllColumns()
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

        $this->columns = array_unique(array_merge($this->columns, $columns));

        return $this;
    }

    /**
     * Get all values
     *
     * @return mixed[][]
     */
    public function getValueCount()
    {
        return $this->valueCount;
    }

    /**
     * Add a set of values
     *
     * @param array $values
     *   Either values are numerically indexed, case in which they must match
     *   the internal columns order, or they can be key-value pairs case in
     *   which matching will be dynamically be done right now
     *
     * @todo
     *   - implement it correctly
     *   - allow arbitrary statement or subqueries?
     *
     * @return $this
     */
    public function values(array $values)
    {
        if (count($values) !== count($this->columns)) {
            throw new QueryError("values count does not match column count");
        }

        foreach ($values as $value) {
            $this->arguments[] = $value;
        }

        $this->valueCount++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
