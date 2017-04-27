<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Runner\RunnerInterface;

/**
 * Base implement for objects that also are RunnerInterface implementations.
 */
trait QueryFactoryRunnerTrait
{
    /**
     * {@inheritdoc}
     */
    final public function select($relation, string $alias = null) : SelectQuery
    {
        $select = new SelectQuery($relation, $alias);
        $select->setRunner($this);

        return $select;
    }

    /**
     * {@inheritdoc}
     */
    final public function update($relation, string $alias = null) : UpdateQuery
    {
        $update = new UpdateQuery($relation, $alias);
        $update->setRunner($this);

        return $update;
    }

    /**
     * {@inheritdoc}
     */
    final public function insertQuery($relation) : InsertQueryQuery
    {
        $insert = new InsertQueryQuery($relation);
        $insert->setRunner($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    final public function insertValues($relation) : InsertValuesQuery
    {
        $insert = new InsertValuesQuery($relation);
        $insert->setRunner($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    final public function delete($relation, string $alias = null) : DeleteQuery
    {
        $insert = new DeleteQuery($relation, $alias);
        $insert->setRunner($this);

        return $insert;
    }
}
