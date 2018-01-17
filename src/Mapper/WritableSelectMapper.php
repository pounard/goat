<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Mapper\Error\EntityNotFoundError;
use Goat\Query\DeleteQuery;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\ReturningQueryInterface;
use Goat\Query\UpdateQuery;

/**
 * Default implementation for the writable mapper trait
 */
class WritableSelectMapper extends SelectMapper implements WritableMapperInterface
{
    /**
     * Implementors must return a correct returninig expression that will
     * hydrate one or more entities
     */
    protected function addReturningToQuery(ReturningQueryInterface $query)
    {
        // Default naive implementation, return everything from the affected
        // tables. Please note that it might not work as expected in case there
        // is join statements or a complex from statement, case in which
        // specific mapper implementations should implement this.
        // Per default, we don't prefix with the mapper relation alias, some
        // fields could be useful to the target entity class, we can't know
        // that without knowing the user's business, so leave it as-is to
        // cover the widest range of use cases possible.
        $query->returning('*');
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $values)
    {
        $query = $this
            ->createInsertValues()
            ->values($values)
        ;

        $this->addReturningToQuery($query);

        if ($className = $this->getClassName()) {
            $result = $query->execute([], $className);
        } else {
            $result = $query->execute();
        }

        if (1 < $result->countRows()) {
            throw new EntityNotFoundError(sprintf("entity counld not be created"));
        }

        return $result->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function createFrom($entity)
    {
        return $this->create($this->extractValues($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id, bool $raiseErrorOnMissing = false)
    {
        $query = $this
            ->createDelete(
                $this->expandPrimaryKey($id)
            )
        ;

        // @todo deal with runner that don't support returning
        $this->addReturningToQuery($query);

        if ($className = $this->getClassName()) {
            $result = $query->execute([], $className);
        } else {
            $result = $query->execute();
        }

        $affected = $result->countRows();
        if ($raiseErrorOnMissing) {
            if (1 < $affected) {
                throw new EntityNotFoundError(sprintf("updated entity does not exist"));
            }
            if (1 > $affected) {
                // @codeCoverageIgnoreStart
                // This can only happen with a misconfigured mapper, a wrongly built
                // select query, or a deficient database (for example MySQL) that
                // which under circumstances may break ACID properties of your data
                // and allow duplicate inserts into tables.
                throw new EntityNotFoundError(sprintf("update affected more than one row"));
                // @codeCoverageIgnoreEnd
            }
        }

        return $result->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $values)
    {
        $query = $this
            ->createUpdate(
                $this->expandPrimaryKey($id)
            )
            ->sets($values)
        ;

        // @todo deal with runner that don't support returning
        $this->addReturningToQuery($query);

        if ($className = $this->getClassName()) {
            $result = $query->execute([], $className);
        } else {
            $result = $query->execute();
        }

        $affected = $result->countRows();
        if (1 < $affected) {
            throw new EntityNotFoundError(sprintf("updated entity does not exist"));
        }
        if (1 > $affected) {
            // @codeCoverageIgnoreStart
            // This can only happen with a misconfigured mapper, a wrongly built
            // select query, or a deficient database (for example MySQL) that
            // which under circumstances may break ACID properties of your data
            // and allow duplicate inserts into tables.
            throw new EntityNotFoundError(sprintf("update affected more than one row"));
            // @codeCoverageIgnoreEnd
        }

        return $result->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function updateFrom($id, $entity)
    {
        return $this->update($id, $this->extractValues($entity));
    }

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
