<?php

namespace Momm\Core\Client;

use Momm\Core\Converter\ConverterAwareInterface;

/**
 * When in use using the iterator, default behavior is to return associative arrays
 */
interface ResultIteratorInterface extends \IteratorAggregate, \Countable, ConverterAwareInterface
{
    /**
     * Get the field count
     *
     * @return int
     */
    public function countFields();

    /**
     * Get the total row count
     *
     * @return int
     */
    public function countRows();

    /**
     * Does this field exists
     *
     * @param string $name
     *
     * @return boolean
     */
    public function fieldExists($name);

    /**
     * Get all field names, in select order
     *
     * @return string[]
     */
    public function getFieldNames();

    /**
     * Get field type
     *
     * @param string $name
     *
     * @return string
     */
    public function getFieldType($name);

    /**
     * Get field name
     *
     * @param int $index
     *
     * @return string
     */
    public function getFieldName($index);

    /**
     * Fetch column
     *
     * @param string $name
     *
     * @return mixed[]
     */
    public function fetchColumn($name);

    /**
     * Fetch row at, do not iterate
     *
     * @param int $index
     *
     * @return mixed[]
     */
    public function fetchRowAt($index);
}
