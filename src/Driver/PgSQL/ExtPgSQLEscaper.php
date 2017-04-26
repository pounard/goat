<?php

declare(strict_types=1);

namespace Goat\Driver\PgSQL;

use Goat\Query\Writer\EscaperBase;

/**
 * SQL escaper using ext_pgsql
 */
class ExtPgSQLEscaper extends EscaperBase
{
    use ExtPgSQLErrorTrait;

    /**
     * @var resource
     */
    private $resource;

    /**
     * Default constructor
     *
     * @param resource $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

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
    public function escapeLiteral(string $string) : string
    {
        if ('' === $string) {
            return '';
        }

        $escaped = @pg_escape_literal($this->resource, $string);
        if (false === $escaped) {
            $this->connectionError($this->resource);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word) : string
    {
        if ('' === $word) {
            return '';
        }

        $escaped = @pg_escape_bytea($this->resource, $word);
        if (false === $escaped) {
            $this->connectionError($this->resource);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string) : string
    {
        if ('' === $string) {
            return '';
        }

        $escaped = @pg_escape_identifier($this->resource, $string);
        if (false === $escaped) {
            $this->connectionError($this->resource);
        }

        return $escaped;
    }
}