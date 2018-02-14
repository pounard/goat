<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

/**
 * MySQL SQL query formatter
 */
class PDOMySQLEscaper extends AbstractPDOEscaper
{
    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences() : array
    {
        return [
            '`',    // Identifier escape character
            '\'',   // String literal escape character
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string) : string
    {
        $this->checkIdentifier($string);

        return '`' . str_replace('`', '``', $string) . '`';
    }
}
