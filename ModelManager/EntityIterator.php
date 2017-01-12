<?php

namespace Goat\ModelManager;

use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Converter\ConverterAwareTrait;
use Goat\Core\Hydrator\HydratorAwareTrait;

/**
 * Basic entity generator implementation, no cache.
 */
class EntityIterator implements \IteratorAggregate, ResultIteratorInterface
{
    use ConverterAwareTrait;
    use HydratorAwareTrait;

    private $result;
    private $structure;
    private $cache;

    /**
     * Default constructor
     *
     * @param ResultIteratorInterface $result
     * @param EntityStructure $structure
     */
    public function __construct(ResultIteratorInterface $result, EntityStructure $structure)
    {
        $this->result = $result;
        $this->structure = $structure;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->result->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        if (null === $this->cache) {
            $this->cache = [];

            foreach ($this->result as $row) {
                $this->cache[] = $this->structure->create($row);
            }
        }

        return new \ArrayIterator($this->cache);
    }

    /**
     * {@inheritdoc}
     */
    public function countColumns() : int
    {
        return $this->result->countColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function countRows() : int
    {
        return $this->result->countRows();
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists(string $name) : bool
    {
        return $this->result->columnExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames() : array
    {
        return $this->result->getColumnNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(string $name) : string
    {
        return $this->result->getColumnType($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index) : string
    {
        return $this->result->getColumnName($index);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchField($name = null)
    {
        return $this->result->fetchField($name);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name = null)
    {
        return $this->result->fetchColumn($name);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        return $this->result->fetch();
    }
}
