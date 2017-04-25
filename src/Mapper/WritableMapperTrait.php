<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Core\Client\ConnectionInterface;
use Goat\Query\DeleteQuery;
use Goat\Query\ExpressionRelation;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\UpdateQuery;

/**
 * Default implementation for the writable mapper trait
 */
trait WritableMapperTrait /* implements WritableMapperInterface */
{
    use MapperTrait;

    /**
     * {@inheritdoc}
     */
    abstract public function getConnection() : ConnectionInterface;

    /**
     * {@inheritdoc}
     */
    abstract public function getRelation() : ExpressionRelation;

    /**
     * {@inheritdoc}
     */
    public function createUpdate($criteria = null) : UpdateQuery
    {
        $update = $this->getConnection()->update($this->getRelation());

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
        $update = $this->getConnection()->delete($this->getRelation());

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
        return $this->getConnection()->insertValues($this->getRelation());
    }

    /**
     * {@inheritdoc}
     */
    public function createInsertQuery() : InsertQueryQuery
    {
        return $this->getConnection()->insertQuery($this->getRelation());
    }
}
