<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Query\DeleteQuery;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\UpdateQuery;

/**
 * Add update and insert functions to mappers.
 *
 * Be aware that this can only write on a single relation at once.
 */
interface WritableMapperInterface extends MapperInterface
{
    /**
     * Create one entity from values
     *
     * @param array $values
     *   Values for the entity
     *
     * @return mixed
     *   The created entity
     */
    public function create(array $values);

    /**
     * Update one entity from another instance values, ideal for form with mapping
     *
     * @param mixed $entity
     *   The entity to duplicate for fields
     *
     * @return mixed
     *   The created entity
     *
     * @throws \Goat\Mapper\Error\EntityNotFoundError
     *   If the entity does not exists
     */
    public function createFrom($entity);

    /**
     * Update one entity with values
     *
     * @param int|string|int[]|string[] $id
     *   Primary key
     * @param bool $throwIfNotExists
     *   Throw exception when entity does not exists
     *
     * @return mixed
     *   The updated entity
     *
     * @throws \Goat\Mapper\Error\EntityNotFoundError
     *   If the entity does not exists and $throwIfNotExists is true
     */
    public function delete($id, bool $raiseErrorOnMissing = false);

    /**
     * Update one entity with values
     *
     * @param int|string|int[]|string[] $id
     *   Primary key
     * @param mixed[] $values
     *   New values to set, can be partial
     *
     * @return mixed
     *   The updated entity
     *
     * @throws \Goat\Mapper\Error\EntityNotFoundError
     *   If the entity does not exists
     */
    public function update($id, array $values);

    /**
     * Update one entity from another instance values, ideal for form with mapping
     *
     * @param int|string|int[]|string[] $id
     *   Primary key
     * @param mixed $entity
     *   The entity to duplicate for fields
     *
     * @return mixed
     *   The updated entity
     *
     * @throws \Goat\Mapper\Error\EntityNotFoundError
     *   If the entity does not exists
     */
    public function updateFrom($id, $entity);

    /**
     * Create update query
     *
     * @param array|\Goat\Query\Expression|\Goat\Query\Where $criteria
     *   This value might be either one of:
     *     - a simple key/value array that will be translated into a where
     *       clause using the AND statement
     *     - a Expression instance
     *     - a Where instance
     *
     * @return UpdateQuery
     */
    public function createUpdate($criteria = null) : UpdateQuery;

    /**
     * Create delete query
     *
     * @param array|\Goat\Query\Expression|\Goat\Query\Where $criteria
     *   This value might be either one of:
     *     - a simple key/value array that will be translated into a where
     *       clause using the AND statement
     *     - a Expression instance
     *     - a Where instance
     *
     * @return UpdateQuery
     */
    public function createDelete($criteria = null) : DeleteQuery;

    /**
     * Create insert query with values
     *
     * @return InsertValuesQuery
     */
    public function createInsertValues() : InsertValuesQuery;

    /**
     * Create insert query from query
     *
     * @return InsertQueryQuery
     */
    public function createInsertQuery() : InsertQueryQuery;


}
