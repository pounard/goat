<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Query\SelectQuery;
use Goat\Runner\RunnerInterface;

/**
 * Default implementation for the writable mapper trait
 */
class WritableSelectMapper extends WritableDefaultMapper
{
    private $select;

    /**
     * Default constructor
     *
     * @param string $class
     *   Default class to use for hydration
     * @param string[] $primaryKey
     *   Primary key column names
     * @param SelectQuery $select
     *   Select query that loads entities
     * @param string[] $columns
     *   Array of known columns
     */
    public function __construct(RunnerInterface $runner, string $class, array $primaryKey, SelectQuery $select, array $columns = [])
    {
        $relation = $select->getRelation();
        if ($schema = $relation->getSchema()) {
            $relationString = $schema.'.'.$relation->getName();
        } else {
            $relationString = $relation->getName();
        }

        parent::__construct($runner, $class, $primaryKey, $relationString, $relation->getAlias(), $columns);

        $this->select = $select;
    }

    /**
     * {@inheritdoc}
     */
    public function createSelect(bool $withColumns = true) : SelectQuery
    {
        $select = clone $this->select;

        if (!$withColumns) {
            $select->removeAllColumns();
        }

        return $select;
    }
}
