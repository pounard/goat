<?php

namespace Goat\Core\Query;

use Goat\Core\Error\QueryError;
use Goat\Core\Query\Partial\AbstractQuery;
use Goat\Core\Query\Partial\ReturningClauseTrait;

/**
 * Represents an INSERT QUERY query
 */
class InsertQueryQuery extends AbstractQuery
{
    use ReturningClauseTrait;

    private $columns = [];
    private $query;

    /**
     * Build a new query
     *
     * @param string $relation
     *   SQL from statement relation name
     */
    public function __construct($relation)
    {
        // INSERT queries main relation cannot be aliased
        parent::__construct($relation);
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
     * Get query
     *
     * @return Query
     */
    public function getQuery()
    {
        if (!$this->query) {
            throw new QueryError("query has not been set yet");
        }

        return $this->query;
    }

    /**
     * Set SELECT query
     *
     * @param Query $query
     *   The query must return something
     *
     * @return $this
     */
    public function query(Query $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->query->getArguments();
    }
}