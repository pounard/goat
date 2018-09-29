<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

/**
 * MySQL SQL query formatter
 */
class PDOPgSQLEscaper extends AbstractPDOEscaper
{
    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences() : array
    {
        return [
            '"',    // Identifier escape character
            '\'',   // String literal escape character
            '$$',   // String constant escape sequence
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string) : string
    {
        return '"' . \str_replace('"', '""', $string) . '"';
    }
}
