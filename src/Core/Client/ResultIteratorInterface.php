<?php

declare(strict_types=1);

namespace Goat\Core\Client;

use Goat\Converter\ConverterAwareInterface;
use Goat\Core\Hydrator\HydratorAwareInterface;

/**
 * When in use using the iterator, default behavior is to return associative arrays
 */
interface ResultIteratorInterface extends \IteratorAggregate, \Countable, ConverterAwareInterface, HydratorAwareInterface
{
    /**
     * Get the column count
     *
     * @return int
     */
    public function countColumns() : int;

    /**
     * Get the total row count
     *
     * @return int
     */
    public function countRows() : int;

    /**
     * Does this column exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function columnExists(string $name) : bool;

    /**
     * Get all column names, in select order
     *
     * @return string[]
     */
    public function getColumnNames() : array;

    /**
     * Get column type
     *
     * @param string $name
     *
     * @return string
     */
    public function getColumnType(string $name) : string;

    /**
     * Get column name
     *
     * @param int $index
     *
     * @return string
     */
    public function getColumnName(int $index) : string;

    /**
     * Fetch given column in the first or current row
     *
     * @param int|string $name
     *   If none given, just take the first one
     *
     * @return mixed[]
     */
    public function fetchField($name = null);

    /**
     * Fetch column
     *
     * @param int|string $name = null
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
