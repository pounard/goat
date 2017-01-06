<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareInterface;

/**
 * When in use using the iterator, default behavior is to return associative arrays
 */
interface ResultIteratorInterface extends \IteratorAggregate, \Countable, ConverterAwareInterface
{
    /**
     * Get the column count
     *
     * @return int
     */
    public function countColumns();

    /**
     * Get the total row count
     *
     * @return int
     */
    public function countRows();

    /**
     * Does this column exists
     *
     * @param string $name
     *
     * @return boolean
     */
    public function columnExists($name);

    /**
     * Get all column names, in select order
     *
     * @return string[]
     */
    public function getColumnNames();

    /**
     * Get column type
     *
     * @param string $name
     *
     * @return string
     */
    public function getColumnType($name);

    /**
     * Get column name
     *
     * @param int $index
     *
     * @return string
     */
    public function getColumnName($index);

    /**
     * Fetch given column in the first or current row
     *
     * @param string $name
     *   If none given, just take the first one
     *
     * @return mixed[]
     */
    public function fetchField($name = null);

    /**
     * Fetch column
     *
     * @param string $name = null
     *   If none given, just take the first one
     *
     * @return mixed[]
     */
    public function fetchColumn($name = null);

    /**
     * Get next element and move forward
     *
     * @return mixed
     */
    public function fetch();
}
