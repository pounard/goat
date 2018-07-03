<?php

declare(strict_types=1);

namespace Goat\Query\Driver;

/**
 * PDO/MySQL < 8
 */
class PDOMySQL5Formatter extends MySQL5Formatter
{
    /**
     * {@inheritdoc}
     */
    protected function writePlaceholder(int $index) : string
    {
        return '?';
    }
}
