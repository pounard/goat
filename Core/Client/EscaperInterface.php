<?php

namespace Goat\Core\Client;

/**
 * By dividing the escaper interface from the connection, we ensure that all
 * other objects are not connection-dependent, at least within interfaces
 */
interface EscaperInterface
{
    /**
     * Escape identifier (ie. table name, variable name, ...)
     *
     * @param string $string
     *
     * @return $string
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
     *
     * @param string $string
     *
     * @return $string
     */
    public function escapeLiteral(string $string) : string;

    /**
     * Escape blob
     *
     * @param string $string
     *
     * @return $string
     */
    public function escapeBlob(string $word) : string;
}
