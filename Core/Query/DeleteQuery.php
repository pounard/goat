<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;
use Goat\Core\Query\Partial\AbstractQuery;
use Goat\Core\Query\Partial\FromClauseTrait;
use Goat\Core\Query\Partial\ReturningClauseTrait;

/**
 * Represents an DELETE query
 */
final class DeleteQuery extends AbstractQuery
{
    use FromClauseTrait;
    use ReturningClauseTrait;

    private $where;

    /**
     * Build a new query
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function __construct($relation, string $alias = null)
    {
        parent::__construct($relation, $alias);

        $this->where = new Where();
    }

    /**
     * Add a condition in the where clause
     *
     * @param string $columnName
     * @param mixed $value
     * @param string $operator
     *
     * @return $this
     */
    public function condition($columnName, $value, string $operator = Where::EQUAL)
    {
        $this->where->condition($columnName, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the where clause
     *
     * @param string $statement
     *   SQL string, which may contain parameters
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     *
     * @return $this
     */
    public function expression($statement, $arguments = [])
    {
        $this->where->expression($statement, $arguments);

        return $this;
    }

    /**
     * Get where statement
     *
     * @return Where
     */
    public function getWhere() : Where
    {
        return $this->where;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        $arguments = new ArgumentBag();

        foreach ($this->joins as $join) {
            $arguments->append($join[1]->getArguments());
        }

        if (!$this->where->isEmpty()) {
            $arguments->append($this->where->getArguments());
        }

        return $arguments;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneJoins();
        $this->where = clone $this->where;
    }
}
