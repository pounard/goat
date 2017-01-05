<?php

namespace Goat\Core\Query;

interface Query
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
     * @param array $parameters
     *   Key/value pairs or argument list, anonymous and named parameters
     *   cannot be mixed up within the same query
     * @param string $class
     *   Object class that the iterator should return
     *
     * @return ResultIteratorInterface
     */
    public function execute($class = Query::RET_PROXY, array $parameters = []);

    /**
     * Get query arguments
     *
     * @return string[]
     */
    public function getArguments();
}
