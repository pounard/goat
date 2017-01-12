<?php

namespace Goat\Core\Query;

/**
 * SQL formatter
 */
interface SqlFormatterInterface
{
    /**
     * Format the query
     *
     * @param Statement $query
     *
     * @return string
     */
    public function format(Statement $query) : string;
}
