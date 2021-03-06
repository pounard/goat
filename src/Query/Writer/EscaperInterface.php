<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

/**
 * By dividing the escaper interface from the connection, we ensure that all
 * other objects are not connection-dependent, at least within interfaces
 */
interface EscaperInterface
{
    /**
     * Escape identifier (ie. table name, variable name, ...)
     */
    public function escapeIdentifier(string $string) : string;

    /**
     * Escape identifier list (ie. table name, variable name, ...)
     *
     * @param string|string[] $strings
     *
     * @return $string
     *   Comma-separated list
     */
    public function escapeIdentifierList($strings) : string;

    /**
     * Escape literal (string)
     */
    public function escapeLiteral(string $string) : string;

    /**
     * Escape like (string)
     */
    public function escapeLike(string $string) : string;

    /**
     * Escape blob
     */
    public function escapeBlob(string $word) : string;

    /**
     * Unescape blob
     */
    public function unescapeBlob($resource) : ?string;

    /**
     * Get backend escape sequences
     *
     * @return string[]
     */
    public function getEscapeSequences() : array;
}
