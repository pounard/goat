<?php

namespace Goat\Core\Query;

use Goat\Core\Client\EscaperAwareInterface;
use Goat\Core\Client\EscaperAwareTrait;
use Goat\Core\Client\EscaperInterface;

/**
 * Standard SQL query formatter
 */
class SqlFormatter implements SqlFormatterInterface, EscaperAwareInterface
{
    use EscaperAwareTrait;

    /**
     * Default constructor
     *
     * @param EscaperInterface $escaper
     */
    public function __construct(EscaperInterface $escaper)
    {
        $this->setEscaper($escaper);
    }

    /**
     * {@inheritdoc}
     */
    public function formatProjection($statement, $alias = null)
    {
        if ($alias) {
            return $statement . ' as ' . $alias;
        }

        return $statement;
    }

    /**
     * Format fields for 'select'
     *
     * @return string
     */
    public function formatProjectionAll(array $columns)
    {
        if (!$columns) {
            return '*';
        }

        $output = [];

        foreach ($columns as $column) {
            $output[] = $this->formatProjection(...$column);
        }

        return implode(', ', $output);
    }

    /**
     * {@inheritdoc}
     */
    public function formatOrderBy($column, $order, $null)
    {
        if (Query::ORDER_ASC === $order) {
            $orderStr = 'asc';
        } else {
            $orderStr = 'desc';
        }

        switch ($null) {

            case Query::NULL_FIRST:
                $nullStr = ' nulls first';
                break;

            case Query::NULL_LAST:
                $nullStr = ' nulls last';
                break;

            case Query::NULL_IGNORE:
            default:
                $nullStr = '';
                break;
        }

        return sprintf('%s %s%s', $column, $orderStr, $nullStr);
    }

    /**
     * {@inheritdoc}
     */
    public function formatOrderByAll(array $orders)
    {
        if (!$orders) {
            return '';
        }

        $output = [];

        foreach ($orders as $data) {
            list($column, $order, $null) = $data;
            $output[] = $this->formatOrderBy($column, $order, $null);
        }

        return "order by " . implode(", ", $output);
    }

    /**
     * {@inheritdoc}
     */
    public function formatGroupBy($column)
    {
        return $column;
    }

    /**
     * {@inheritdoc}
     */
    public function formatGroupByAll(array $groups)
    {
        if (!$groups) {
            return '';
        }

        $output = [];

        foreach ($groups as $column) {
            $output[] = $this->formatGroupBy($column);
        }

        return "group by " . implode(", ", $output);
    }

    /**
     * {@inheritdoc}
     */
    public function formatJoin($mode, $relation, $alias, Where $condition)
    {
        switch ($mode) {

            case Query::JOIN_NATURAL:
                $prefix = 'natural join';
                break;

            case Query::JOIN_LEFT:
            case Query::JOIN_LEFT_OUTER:
                $prefix = 'left outer join';
                break;

            case Query::JOIN_INNER:
            default:
                $prefix = 'inner join';
                break;
        }

        if ($condition->isEmpty()) {
            return sprintf("%s %s %s", $prefix, $relation, $alias);
        } else {
            return sprintf("%s %s %s on (%s)", $prefix, $relation, $alias, $condition);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function formatJoinAll(array $joins)
    {
        if (!$joins) {
            return '';
        }

        $output = [];

        foreach ($joins as $alias => $join) {
            list($relation, $condition, $mode) = $join;
            $output[] = $this->formatJoin($mode, $relation, $alias, $condition);
        }

        return implode("\n", $output);
    }

    /**
     * {@inheritdoc}
     */
    public function formatRange($limit = 0, $offset = 0)
    {
        if ($limit) {
            return sprintf('limit %d offset %d', $limit, $offset);
        } else if ($offset) {
            return sprintf('offset %d', $offset);
        } else {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function format(SelectQuery $query)
    {
        $output = [];
        $output[] = sprintf(
            "select %s\nfrom %s %s\n%s",
            $this->formatProjectionAll($query->getAllColumns()),
            $query->getRelation(),
            $query->getRelationAlias(),
            $this->formatJoinAll($query->getAllJoin())
        );

        $where = $query->where();
        if (!$where->isEmpty()) {
            $output[] = sprintf('where %s', $where);
        }

        $output[] = $this->formatGroupByAll($query->getAllGroupBy());
        $output[] = $this->formatOrderByAll($query->getAllOrderBy());
        $output[] = $this->formatRange(...$query->getRange());

        $having = $query->having();
        if (!$having->isEmpty()) {
            $output[] = sprintf('having %s', $having);
        }

        return implode("\n", $output);
    }
}
