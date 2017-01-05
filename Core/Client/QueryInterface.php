<?php

namespace Goat\Core\Client;

/**
 * Reprensents an executable query
 */
interface QueryInterface
{
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
     */
    public function execute($parameters, $class = self::RET_PROXY);
}
