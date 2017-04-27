<?php

namespace Goat\Runner;

use Goat\Query\Query;

/**
 * Stripped down representation of a connection/driver that can run queries.
 */
interface RunnerInterface
{
    /**
     * Send query
     *
     * @param string|Query $query
     *   If a query is given here, and parameters is empty, it will use the
     *   Query instance parameters, but if you provide parameters, it will
     *   override them
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
    public function query($query, array $parameters = null, $options = null) : ResultIteratorInterface;

    /**
     * Perform only, do not return a result but affected row count instead
     *
     * @param string|Query $query
     *   If a query is given here, and parameters is empty, it will use the
     *   Query instance parameters, but if you provide parameters, it will
     *   override them
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
    public function perform($query, array $parameters = null, $options = null) : int;

    /**
     * Prepare query
     *
     * @param string|Query $query
     *   Bare SQL or Query instance
     * @param string $identifier
     *   Query unique identifier, if null given one will be generated
     *
     * @return string
     *   The given or generated identifier
     */
    public function prepareQuery($query, string $identifier = null) : string;

    /**
     * Prepare query
     *
     * @param string $identifier
     *   Query unique identifier
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
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface;
}
