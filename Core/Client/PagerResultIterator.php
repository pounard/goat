<?php

namespace Goat\Core\Client;

/**
 * Wraps a result iterator in order to paginate results
 */
final class PagerResultIterator implements ResultIteratorInterface, \IteratorAggregate, \Countable
{
    private $result;
    private $count;
    private $limit;
    private $page;

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
    public function __construct(ResultIteratorInterface $result, $count, $limit, $page)
    {
        $this->result = $result;
        $this->count  = $count;
        $this->limit  = $limit;
        $this->page   = $page;
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
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get the number of results in this page
     *
     * @return int
     */
    public function getCurrentCount()
    {
        return $this->result->countRows();
    }

    /**
     * Get the index of the first element of this page
     *
     * @return int
     */
    public function getStartOffset()
    {
        return ($this->page - 1) * $this->limit;
    }

    /**
     * Get the index of the last element of this page
     *
     * @return int
     */
    public function getStopOffset()
    {
        return $this->getStartOffset() + $this->getCurrentCount();
    }

    /**
     * Get the last page number
     *
     * @return int
     */
    public function getLastPage()
    {
        return (int)max(1, ceil($this->count / $this->limit));
    }

    /**
     * Get current page number
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->page;
    }

    /**
     * Is there a next page
     *
     * @return boolean
     */
    public function hasNextPage()
    {
        return (bool)($this->page < $this->getLastPage());
    }

    /**
     * Is there a previous page
     *
     * @return boolean
     */
    public function hasPreviousPage()
    {
        return (bool)(1 < $this->page);
    }

    /**
     * Get the total number of results in all pages
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->count;
    }

    /**
     * Get maximum result per page.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function countFields()
    {
        return $this->result->countFields();
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
    public function fieldExists($name)
    {
        return $this->result->fieldExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldNames()
    {
        return $this->result->getFieldNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType($name)
    {
        return $this->result->getFieldType($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldName($index)
    {
        return $this->result->getFieldName($index);
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
    public function fetchColumn($name)
    {
        return $this->result->fetchColumn($name);
    }
}
