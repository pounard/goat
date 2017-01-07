<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;
use Goat\Core\Client\ArgumentHolderInterface;
use Goat\Core\Client\ResultIteratorInterface;

interface Query extends ArgumentHolderInterface
{
    const JOIN_INNER = 4;
    const JOIN_LEFT = 2;
    const JOIN_LEFT_OUTER = 3;
    const JOIN_NATURAL = 1;

    const NULL_FIRST = 2;
    const NULL_IGNORE = 0;
    const NULL_LAST = 1;
    const ORDER_ASC = 1;
    const ORDER_DESC = 2;

    const RET_ARRAY = 'array';
    const RET_PROXY = null;
    const RET_STDCLASS = '\stdClass';

    /**
     * Execute query with the given parameters and return the result iterator
     *
     * @param string $class
     *   Object class that the iterator should return
     * @param array $parameters
     *   Key/value pairs or argument list, anonymous and named parameters
     *   cannot be mixed up within the same query
     *
     * @return ResultIteratorInterface
     */
    public function execute($class = Query::RET_PROXY, array $parameters = []);

    /**
     * Execute query with the given parameters and return the affected row count
     *
     * @param array $parameters
     *   Key/value pairs or argument list, anonymous and named parameters
     *   cannot be mixed up within the same query
     *
     * @return int
     */
    public function perform(array $parameters = []);

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
     * @return boolean
     */
    public function willReturnRows();
}
