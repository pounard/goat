<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterMap;
use Goat\Error\GoatError;
use Goat\Hydrator\HydratorInterface;

/**
 * Wraps a result iterator in order to paginate results
 */
final class PagerResultIterator implements ResultIteratorInterface
{
    private $result;
    private $count = 0;
    private $limit = 0;
    private $page = 0;

    /**
     * Default constructor
     *
     * @param ResultIteratorInterface $result
     * @param int $count
     *   Total number of results.
     * @param int $limit
     *   Results per page
     * @param int $page
     *   Current page number (starts at 1)
     */
    public function __construct(ResultIteratorInterface $result, int $count, int $limit, int $page)
    {
        if ($page < 1) {
            throw new GoatError(sprintf("page numbering starts with 1, %d given", $page));
        }

        $this->result = $result;
        $this->count  = $count;
        $this->limit  = $limit;
        $this->page   = $page;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterMap $converter)
    {
        return $this->result->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
        return $this->result->setHydrator($hydrator);
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
        return $this->result;
    }

    /**
     * Get attached result iterator
     *
     * @return ResultIteratorInterface
     */
    public function getResult() : ResultIteratorInterface
    {
        return $this->result;
    }

    /**
     * Get the number of results in this page
     *
     * @return int
     */
    public function getCurrentCount() : int
    {
        return $this->result->countRows();
    }

    /**
     * Get the index of the first element of this page
     *
     * @return int
     */
    public function getStartOffset() : int
    {
        return ($this->page - 1) * $this->limit;
    }

    /**
     * Get the index of the last element of this page
     *
     * @return int
     */
    public function getStopOffset() : int
    {
        $stopOffset = $this->getStartOffset() + $this->getCurrentCount();

        if ($this->count < $stopOffset) {
            $stopOffset = $this->count;
        }

        return $stopOffset;
    }

    /**
     * Get the last page number
     *
     * @return int
     */
    public function getLastPage() : int
    {
        return (int)max(1, ceil($this->count / $this->limit));
    }

    /**
     * Get current page number
     *
     * @return int
     */
    public function getCurrentPage() : int
    {
        return $this->page;
    }

    /**
     * Is there a next page
     *
     * @return bool
     */
    public function hasNextPage() : bool
    {
        return $this->page < $this->getLastPage();
    }

    /**
     * Is there a previous page
     *
     * @return bool
     */
    public function hasPreviousPage() : bool
    {
        return 1 < $this->page;
    }

    /**
     * Get the total number of results in all pages
     *
     * @return int
     */
    public function getTotalCount() : int
    {
        return $this->count;
    }

    /**
     * Get maximum result per page.
     *
     * @return int
     */
    public function getLimit() : int
    {
        return $this->limit;
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
