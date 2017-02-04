<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Core\Client\ConnectionAwareInterface;
use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Client\PagerResultIterator;
use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Query\Expression;
use Goat\Core\Query\Where;
use Goat\Mapper\Error\EntityNotFoundError;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\ExpressionRelation;

/**
 * Maps immutable entities on SQL projections, and provides a set of utilities
 * to load, filter, paginate them.
 *
 * Insertion, update and delete should happen at the table level, and will not
 * be handled by the mapper interface.
 */
interface MapperInterface extends ConnectionAwareInterface
{
    /**
     * Get connection
     *
     * @return ConnectionInterface
     */
    public function getConnection() : ConnectionInterface;

    /**
     * Get entity class name
     *
     * @return string
     */
    public function getClassName() : string;

    /**
     * Get relation this mapper works on
     *
     * @return ExpressionRelation
     */
    public function getRelation() : ExpressionRelation;

    /**
     * Create a select query based upon this mapper definition
     *
     * @return SelectQuery
     */
    public function createSelect() : SelectQuery;

    /**
     * Find a single object
     *
     * @param int|string|int[]|string[] $id
     *   If primary key of the target relation is multiple, you need to pass
     *   here an array of values, if not, you may pass an array with a single
     *   value or the primary key value directly.
     *
     * @throws EntityNotFoundError
     *   If the entity does not exist in database
     *
     * @return mixed
     *   Loaded entity
     */
    public function findOne($id);

    /**
     * Is there objects existing with the given criteria
     *
     * @param array|Expression|Where $criteria
     *   This value might be either one of:
     *     - a simple key/value array that will be translated into a where
     *       clause using the AND statement
     *     - a Expression instance
     *     - a Where instance
     *
     * @return bool
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
     * @throws EntityNotFoundError
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
     * @param array|Expression|Where $criteria
     *   This value might be either one of:
     *     - a simple key/value array that will be translated into a where
     *       clause using the AND statement
     *     - a Expression instance
     *     - a Where instance
     * @param bool $raiseErrorOnMissing
     *   If this is set to true, and objects could not be found in the database
     *   this will raise exceptions
     *
     * @throws EntityNotFoundError
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
     * @param array|Expression|Where $criteria
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
     * @param array|Expression|Where $criteria
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
