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
     * @param string|Statement $query
     *
     * @return string
     */
    public function format($query);
}
