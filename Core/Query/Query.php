<?php

declare(strict_types=1);

namespace Goat\Core\Query;

use Goat\Core\Client\ResultIteratorInterface;

interface Query extends Statement
{
    const JOIN_INNER = 4;
    const JOIN_LEFT = 2;
    const JOIN_LEFT_OUTER = 3;
    const JOIN_RIGHT = 5;
    const JOIN_RIGHT_OUTER = 6;
    const JOIN_NATURAL = 1;

    const NULL_FIRST = 2;
    const NULL_IGNORE = 0;
    const NULL_LAST = 1;
    const ORDER_ASC = 1;
    const ORDER_DESC = 2;

    /**
     * Execute query with the given parameters and return the result iterator
     *
     * @param mixed[] $parameters
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return ResultIteratorInterface
     */
    public function execute(array $parameters = [], $options = null) : ResultIteratorInterface;

    /**
     * Execute query with the given parameters and return the affected row count
     *
     * @param mixed[] $parameters
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return int
     */
    public function perform(array $parameters = [], $options = null) : int;

    /**
     * Should this query return something
     *
     * For INSERT, MERGE, UPDATE or DELETE queries without a RETURNING clause
     * this should return false, same goes for PostgresSQL PERFORM.
     *
     * Note that SELECT queries might also be run with a PERFORM returning
     * nothing, for example in some cases with FOR UPDATE.
     *
     * This may trigger some optimizations, for example with PDO this will
     * force the RETURN_AFFECTED behavior.
     *
     * @return bool
     */
    public function willReturnRows() : bool;
}
