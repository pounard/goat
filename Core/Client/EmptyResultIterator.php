<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareTrait;
use Goat\Core\Error\InvalidDataAccessError;

/**
 * Empty iterator for some edge cases results
 */
final class EmptyResultIterator implements ResultIteratorInterface
{
    use ConverterAwareTrait;

    private $affectedRowCount = 0;

    /**
     * Default constructor
     *
     * @param number $affectedRows
     */
    public function __construct($affectedRowCount = 0)
    {
        $this->affectedRowCount = $affectedRowCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \EmptyIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function countColumns()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function countRows()
    {
        return $this->affectedRowCount;
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists($name)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType($name)
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName($index)
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function fetchField($name = null)
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name = null)
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        return null;
    }
}
