<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Query\ExpressionRelation;
use Goat\Query\SelectQuery;
use Goat\Runner\PagerResultIterator;
use Goat\Runner\ResultIteratorInterface;
use Goat\Runner\RunnerAwareInterface;
use Goat\Runner\RunnerInterface;

/**
 * Maps immutable entities on SQL projections, and provides a set of utilities
 * to load, filter, paginate them.
 *
 * Insertion, update and delete should happen at the table level, and will not
 * be handled by the mapper interface.
 */
interface MapperInterface extends RunnerAwareInterface
{
    /**
     * Get runner
     */
    public function getRunner() : RunnerInterface;

    /**
     * Get entity class name
     */
    public function getClassName() : string;

    /**
     * Get relation this mapper works on
     */
    public function getRelation() : ExpressionRelation;

    /**
     * Create a select query based upon this mapper definition
     */
    public function createSelect() : SelectQuery;

    /**
     * Does this mapper has a primary key defined
     */
    public function hasPrimaryKey() : bool;

    /**
     * Get primary key column count
     */
    public function getPrimaryKeyCount() : int;

    /**
     * Get primary key fields
     *
     * @throws \Goat\Error\GoatError
     *   If primary is not defined
     *
     * @return string[]
     */
    public function getPrimaryKey() : array;

    /**
     * Find a single object
     *
     * @param int|string|int[]|string[] $id
     *   If primary key of the target relation is multiple, you need to pass
     *   here an array of values, if not, you may pass an array with a single
     *   value or the primary key value directly.
     *
     * @throws \Goat\Mapper\Error\EntityNotFoundError
     *   If the entity does not exist in database
     *
     * @return mixed
     *   Loaded entity
     */
    public function findOne($id);

    /**
     * Is there objects existing with the given criteria
     *
     * @param array|\Goat\Query\Expression|\Goat\Query\Where $criteria
     *   This value might be either one of:
     *     - a simple key/value array that will be translated into a where
     *       clause using the AND statement
     *     - a Expression instance
     *     - a Where instance
     */
    public function exists($criteria) : bool;

    /**
     * Find all object with the given primary keys
     *
     * @param array $idList
     *   Values are either single values, or array of values, depending on if
     *   the primary key is multiple or not
     * @param bool $raiseErrorOnMissing
     *   If this is set to true, and objects could not be found in the database
     *   this will raise exceptions
     *
     * @throws \Goat\Mapper\Error\EntityNotFoundError
     *   If the $raiseErrorOnMissing is set to true and one or more entities do
     *   not exist in database
     *
     * @return mixed[]|ResultIteratorInterface
     *   An iterator on the loaded object
     */
    public function findAll(array $idList, bool $raiseErrorOnMissing = false) : ResultIteratorInterface;

    /**
     * Alias of findBy() that returns a single instance
     *
     *
     * @param array|\Goat\Query\Expression|\Goat\Query\Where $criteria
     *   This value might be either one of:
     *     - a simple key/value array that will be translated into a where
     *       clause using the AND statement
     *     - a Expression instance
     *     - a Where instance
     * @param bool $raiseErrorOnMissing
     *   If this is set to true, and objects could not be found in the database
     *   this will raise exceptions
     *
     * @throws \Goat\Mapper\Error\EntityNotFoundError
     *   If the $raiseErrorOnMissing is set to true and one or more entities do
     *   not exist in database
     *
     * @return mixed
     *   Loaded entity
     */
    public function findFirst($criteria, bool $raiseErrorOnMissing = false);

    /**
     * Find all objects matching the given criteria
     *
     * @param array|\Goat\Query\Expression|\Goat\Query\Where $criteria
     *   This value might be either one of:
     *     - a simple key/value array that will be translated into a where
     *       clause using the AND statement
     *     - a Expression instance
     *     - a Where instance
     * @param int $limit
     *   Set a limit for the pagination
     * @param int $page
     *   Set a page, always starts with 1
     *
     * @return mixed[]|ResultIteratorInterface
     */
    public function findBy($criteria, int $limit = 0, int $offset = 0) : ResultIteratorInterface;

    /**
     * Find all objects matching the given criteria with a pager
     *
     * @param array|\Goat\Query\Expression|\Goat\Query\Where $criteria
     *   This value might be either one of:
     *     - a simple key/value array that will be translated into a where
     *       clause using the AND statement
     *     - a Expression instance
     *     - a Where instance
     * @param int $limit
     *   Set a limit for the pagination
     * @param int $page
     *   Set a page, always starts with 1
     *
     * @return mixed[]|PagerResultIterator
     */
    public function paginate($criteria, int $limit = 0, int $page = 1) : PagerResultIterator;
}
