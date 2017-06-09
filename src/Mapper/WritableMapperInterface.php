<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Query\DeleteQuery;
use Goat\Query\Expression;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\UpdateQuery;
use Goat\Query\Where;

/**
 * Add update and insert functions to mappers.
 *
 * Be aware that this can only write on a single relation at once.
 */
interface WritableMapperInterface extends MapperInterface
{
    /**
     * Create update query
     *
     * @param array|Expression|Where $criteria
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
     * @param array|Expression|Where $criteria
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
