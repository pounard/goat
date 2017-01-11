<?php

namespace Goat\Core\Query\Partial;

use Goat\Core\Client\ConnectionAwareInterface;
use Goat\Core\Client\ConnectionAwareTrait;
use Goat\Core\Error\GoatError;
use Goat\Core\Query\ExpressionRelation;
use Goat\Core\Query\Query;

/**
 * Reprensents the basis of an SQL query.
 */
abstract class AbstractQuery implements Query, ConnectionAwareInterface
{
    use ConnectionAwareTrait;
    use AliasHolderTrait;

    private $relation;

    /**
     * Build a new query
     *
     * @param string $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function __construct($relation, $alias = null)
    {
        $this->relation = $this->normalizeRelation($relation, $alias);
    }

    /**
     * Get SQL from relation
     *
     * @return ExpressionRelation
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * {@inheritdoc}
     */
    final public function execute(array $parameters = [], $options = null)
    {
        if (!$this->connection) {
            throw new GoatError("this query has no reference to any connection, therefore cannot execute itself");
        }

        return $this->connection->query($this, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function perform(array $parameters = [], $options = null)
    {
        if (!$this->connection) {
            throw new GoatError("this query has no reference to any connection, therefore cannot execute itself");
        }

        return $this->connection->perform($this, $parameters, $options);
    }
}
