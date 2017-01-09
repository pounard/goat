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
     * @param Query|Where|ExpressionInterface|string $query
     *
     * @return string
     */
    public function format($query);
}
