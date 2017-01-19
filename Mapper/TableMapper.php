<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Error\ConfigurationError;
use Goat\Core\Query\Query;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\Where;

/**
 * Mapper implementation that is based upon the SelectMapper, it builds the
 * select from a raw array definition.
 */
class TableMapper extends SelectMapper
{
    private $relation;
    private $relationAlias;
    private $joins = [];

    /**
     * Default constructor
     *
     * @param ConnectionInterface $connection
     *   Connection is mandatory in order to build the select query
     * @param string $class
     *   Default class to use for hydration
     * @param string[] $primaryKey
     *   Primary key column names
     * @param string|array $definition
     *   Informative array containing from table and various joins that are
     *   essential for fetching the data. If a string is given, consider it's
     *   just the main relation to use, and relation alias will be the relation
     *   name, if it's an array, it may contain the following values:
     *     - relation: (string|ExpressionRelation, mandatory) the relation name
     *     - alias: (string, optional) the relation alias
     *     - joins: an array of values, each value must be an array with the
     *       following values:
     *         - relation: (string|ExpressionRelation, mandatory) the joined
     *           relation name
     *         - alias: (string, optional) the joined relation alias
     *         - condition: must be a string condition, in this specific case
     *           Where or Expression instances are not possible
     *         - mode: the join mode, must be a Query::JOIN_* constant, default
     *           is INNER JOIN
     */
    public function __construct(ConnectionInterface $connection, string $class, array $primaryKey, $definition)
    {
        $this->parseDefinition($definition);

        parent::__construct($connection, $class, $primaryKey, $this->createSelect($connection));
    }

    /**
     * Parse definition
     *
     * @param string|array $definition
     */
    private function parseDefinition($definition)
    {
        if (is_string($definition)) {
            $definition = ['relation' => $definition];
        }
        if (!is_array($definition)) {
            throw new ConfigurationError("invalid FROM definition given, must be a relation name or a descriptive array");
        }

        if (empty($definition['relation'])) {
            throw new ConfigurationError("invalid FROM definition given, it must at least contain the 'relation' value");
        }
        $this->relation = $definition['relation'];
        if (!empty($definition['alias'])) {
            $this->relationAlias = $definition['alias'];
        }

        if (!empty($definition['joins'])) {
            foreach ($definition['joins'] as $joinDefinition) {
                if (empty($joinDefinition['relation'])) {
                    throw new ConfigurationError("invalid JOIN definition given, it must at least contain the 'relation' value");
                }
                $joinDefinition += ['condition' => null, 'alias' => $joinDefinition['relation'], 'mode' => Query::JOIN_INNER];
                $this->joins[] = $joinDefinition;
            }
        }
    }

    /**
     * Create base select clause from FROM and JOIN definitions
     *
     * @return SelectQuery
     */
    private function createSelect(ConnectionInterface $connection) : SelectQuery
    {
        $select = $connection->select($this->relation, $this->relationAlias);

        foreach ($this->joins as $join) {
            $select->join($join['relation'], $join['condition'], $join['alias'], $join['mode']);
            $select->column($join['alias'] . '.*');
        }

        // Main relation columns are prioritized over joins
        $select->column(($this->relationAlias ?? $this->relation) . '.*');

        return $select;
    }
}
