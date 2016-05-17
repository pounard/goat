<?php

namespace Momm\ModelManager;

use Momm\Core\Client\ResultIteratorInterface;

/**
 * Basic entity generator implementation, no cache.
 */
class EntityIterator implements \IteratorAggregate
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
    public function getIterator()
    {
        if (null === $this->cache) {
            foreach ($this->result as $row) {
                $this->cache[] = $this->structure->create($row);
            }
        }

        return new \ArrayIterator($this->cache);
    }
}
