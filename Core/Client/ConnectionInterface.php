<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareInterface;
use Goat\Core\Query\RawStatement;
use Goat\Core\Query\SelectQuery;

interface ConnectionInterface extends ConverterAwareInterface, EscaperInterface
{
    /**
     * Send query
     *
     * @param string|SelectQuery|RawStatement $sql
     * @param mixed[] $parameters
     *   Query parameters
     * @param boolean $enableConverters
     *   If set to false, converters would be disabled
     *
     * @return ResultIteratorInterface
     */
    public function query($sql, array $parameters = [], $enableConverters = true);

    /**
     * Prepare query
     *
     * @param string|SelectQuery|RawStatement $sql
     *   Bare SQL
     * @param string $identifier
     *   Query unique identifier, if null given one will be generated
     *
     * @return string
     *   The given or generated identifier
     */
    public function prepareQuery($sql, $identifier = null);

    /**
     * Prepare query
     *
     * @param string $identifier
     *   Query unique identifier
     * @param mixed[] $parameters
     *   Query parameters
     * @param boolean $enableConverters
     *   If set to false, converters would be disabled
     *
     * @return ResultIteratorInterface
     */
    public function executePreparedQuery($identifier, array $parameters = [], $enableConverters = true);

    /**
     * Create a select query builder
     *
     * @param string $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return SelectQuery
     */
    public function select($relation, $alias = null);

    /**
     * Set connection encoding
     *
     * @param string $encoding
     */
    public function setClientEncoding($encoding);

    /**
     * Allows the driver to proceed to different type cast
     *
     * Use this if you want to keep a default implementation for a specific
     * type and don't want to override it.
     *
     * @param string $type
     *   The internal type carried by converters
     *
     * @return string
     *   The real type the server will understand
     */
    public function getCastType($type);
}
