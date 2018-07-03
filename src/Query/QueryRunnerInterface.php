<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Runner\ResultIteratorInterface;

interface QueryRunnerInterface
{
    /**
     * Execute query with the given parameters and return the result iterator
     *
     * @param string|Query $query
     *   Arbitrary query to execute
     * @param mixed[] $parameters
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return ResultIteratorInterface
     *   If query is a Query instance and ::willReturnRows() returns false, the
     *   returned result iterator will be empty and nothing will be returned, not
     *   even the affected row count
     */
    public function execute($query, array $parameters = [], $options = null) : ResultIteratorInterface;

    /**
     * Execute query with the given parameters and return the affected row count
     *
     * @param string|Query $query
     *   Arbitrary query to execute
     * @param mixed[] $parameters
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return int
     *   Affected row count if relevant, otherwise 0
     */
    public function perform($query, array $parameters = [], $options = null) : int;
}
