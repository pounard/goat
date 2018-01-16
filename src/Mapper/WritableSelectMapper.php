<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Query\DeleteQuery;
use Goat\Query\ExpressionRelation;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\UpdateQuery;
use Goat\Runner\RunnerInterface;

/**
 * Default implementation for the writable mapper trait
 */
trait WritableMapperTrait /* implements WritableMapperInterface */
{
    use MapperTrait;

    /**
     * {@inheritdoc}
     */
    abstract public function getRunner() : RunnerInterface;

    /**
     * {@inheritdoc}
     */
    abstract public function getRelation() : ExpressionRelation;

    /**
     * {@inheritdoc}
     */
    public function createUpdate($criteria = null) : UpdateQuery
    {
        $update = $this->getRunner()->update($this->getRelation());

        if ($criteria) {
            $update->expression($this->createWhereWith($criteria));
        }

        return $update;
    }

    /**
     * {@inheritdoc}
     */
    public function createDelete($criteria = null) : DeleteQuery
    {
        $update = $this->getRunner()->delete($this->getRelation());

        if ($criteria) {
            $update->expression($this->createWhereWith($criteria));
        }

        return $update;
    }

    /**
     * {@inheritdoc}
     */
    public function createInsertValues() : InsertValuesQuery
    {
        return $this->getRunner()->insertValues($this->getRelation());
    }

    /**
     * {@inheritdoc}
     */
    public function createInsertQuery() : InsertQueryQuery
    {
        return $this->getRunner()->insertQuery($this->getRelation());
    }
}
