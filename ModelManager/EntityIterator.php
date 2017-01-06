<?php

namespace Goat\ModelManager;

use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Converter\ConverterInterface;

/**
 * Basic entity generator implementation, no cache.
 */
class EntityIterator implements \IteratorAggregate, ResultIteratorInterface
{
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
    public function setConverter(ConverterInterface $converter)
    {
        throw new \LogicException("you cannot change entity iterator converter");
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
            foreach ($this->result as $row) {
                $this->cache[] = $this->structure->create($row);
            }
        }

        return new \ArrayIterator($this->cache);
    }

    /**
     * {@inheritdoc}
     */
    public function countColumns()
    {
        return $this->result->countColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function countRows()
    {
        return $this->result->countRows();
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists($name)
    {
        return $this->result->columnExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames()
    {
        return $this->result->getColumnNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType($name)
    {
        return $this->result->getColumnType($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName($index)
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
