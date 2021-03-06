<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Error\InvalidDataAccessError;
use Goat\Hydrator\HydratorInterface;

/**
 * Empty iterator for some edge cases results
 */
final class EmptyResultIterator implements ResultIteratorInterface
{
    private $affectedRowCount = 0;

    /**
     * Default constructor
     *
     * @param number $affectedRows
     */
    public function __construct(int $affectedRowCount = 0)
    {
        $this->affectedRowCount = $affectedRowCount;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setKeyColumn(string $name) : ResultIteratorInterface
    {
        return $this;
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
    public function countColumns() : int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function countRows() : int
    {
        return $this->affectedRowCount;
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists(string $name) : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames() : array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(string $name) : string
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index) : string
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
