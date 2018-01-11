<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Query\Writer\Formatter;

/**
 * MySQL SQL query formatter
 */
class PDOPgSQLFormatter extends Formatter
{
    /**
     * {@inheritdoc}
     */
    protected function writePlaceholder(int $index) : string
    {
        return '?';
    }

    /**
     * {@inheritdoc}
     */
    protected function writeCast(string $placeholder, string $type) : string
    {
        // No surprises there, PostgreSQL is very straight-forward and just
        // uses the datatypes as it handles it. Very stable and robust.
        return sprintf("%s::%s", $placeholder, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatInsertNoValuesStatement() : string
    {
        return "DEFAULT VALUES";
    }
}
