<?php

namespace Goat\Core\Query;

use Goat\Core\Client\EscaperAwareInterface;
use Goat\Core\Client\EscaperAwareTrait;
use Goat\Core\Client\EscaperInterface;
use Goat\Core\Error\NotImplementedError;
use Goat\Core\Error\QueryError;

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
     * {@inheritdoc}
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
    public function formatReturning($statement, $alias = null)
    {
        return $this->formatProjection($statement, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function formatReturningAll(array $return)
    {
        return $this->formatProjectionAll($return);
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
            return sprintf("%s %s %s on (%s)", $prefix, $relation, $alias, $this->formatWhere($condition));
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
     * Create placeholder list for the given arguments
     *
     * This will be used only in order to build 'in' and 'not in' conditions
     *
     * @param mixed[] $arguments
     *   Arbitrary arguments
     * @param string $type = null
     *   Data type of arguments
     *
     * @return string
     */
    protected function formatPlaceholders($arguments, $type = '')
    {
        return implode(', ', array_map(function () { return '$*'; }, $arguments));
    }

    /**
     * {@inheritdoc}
     */
    public function formatWhere(Where $where)
    {
        if ($where->isEmpty()) {
            // Definitely legit (except for pgsql which awaits a boolean)
            return '1';
        }

        $output = [];

        foreach ($where->getConditions() as $condition) {
            if ($condition instanceof Where) {

                if (!$condition->isEmpty()) {
                    $output[] = "(\n" . $this->formatWhere($condition) . "\n)";
                }

            } else {
                list($column, $value, $operator) = $condition;


                if ($value instanceof RawStatement) {
                    $output[] = sprintf('%s %s %s', $column, $operator, $value);
                } else {
                    switch ($operator) {

                        case Where::ARBITRARY:
                            $output[] = $column;
                            break;

                        case Where::IS_NULL:
                        case Where::NOT_IS_NULL:
                            $output[] = sprintf('%s %s', $column, $operator);
                            break;

                        case Where::IN:
                        case Where::NOT_IN:
                            $output[] = sprintf('%s %s (%s)', $column, $operator, $this->formatPlaceholders($value));
                            break;

                        case Where::BETWEEN:
                        case Where::NOT_BETWEEN:
                            $output[] = sprintf('%s %s $* and $*', $column, $operator);
                            break;

                        default:
                            $output[] = sprintf('%s %s $*', $column, $operator);
                            break;
                    }
                }
            }
        }

        return implode("\n" . $where->getOperator() . ' ', $output);
    }

    /**
     * Format given insert query
     *
     * @param InsertQuery $query
     *
     * @return string
     */
    protected function formatValuesInsert(InsertValuesQuery $query)
    {
        $output = [];

        $escaper = $this->escaper;
        $columns = $query->getAllColumns();
        $valueCount = $query->getValueCount();

        if (!$valueCount) {
            throw new QueryError("cannot insert no values");
        }

        $output[] = sprintf(
            "insert into %s",
            $this->escaper->escapeIdentifier($query->getRelation())
        );

        if ($columns) {
            $output[] = sprintf(
                "(%s) values",
                implode(', ', array_map(function ($column) use ($escaper) {
                    return $escaper->escapeIdentifier($column);
                }, $columns))
            );
        }

        $values = [];
        for ($i = 0; $i < $valueCount; ++$i) {
            $values[] = sprintf(
                "(%s)",
                implode(', ', array_fill(0, count($columns), '?'))
            );
        }
        $output[] = implode(', ', $values);

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = sprintf("returning %s", $this->formatReturningAll($return));
        }

        return implode("\n", $output);
    }

    /**
     * Format given insert query
     *
     * @param InsertQueryQuery $query
     *
     * @return string
     */
    protected function formatQueryInsert(InsertQueryQuery $query)
    {
        $output = [];

        $escaper = $this->escaper;
        $columns = $query->getAllColumns();
        $subQuery = $query->getQuery();

        $output[] = sprintf(
            "insert into %s",
            $this->escaper->escapeIdentifier($query->getRelation())
        );

        if ($columns) {
            $output[] = sprintf(
                "(%s) values",
                implode(', ', array_map(function ($column) use ($escaper) {
                    return $escaper->escapeIdentifier($column);
                }, $columns))
            );
        }

        $output[] = $this->format($subQuery);

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = sprintf("returning %s", $this->formatReturningAll($return));
        }

        return implode("\n", $output);
    }

    /**
     * Format given select query
     *
     * @param SelectQuery $query
     *
     * @return string
     */
    protected function formatSelect(SelectQuery $query)
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
            $output[] = sprintf('where %s', $this->formatWhere($where));
        }

        $output[] = $this->formatGroupByAll($query->getAllGroupBy());
        $output[] = $this->formatOrderByAll($query->getAllOrderBy());
        $output[] = $this->formatRange(...$query->getRange());

        $having = $query->having();
        if (!$having->isEmpty()) {
            $output[] = sprintf('having %s', $this->formatWhere($having));
        }

        return implode("\n", $output);
    }

    /**
     * {@inheritdoc}
     */
    public function format($query)
    {
        if ($query instanceof SelectQuery) {
            return $this->formatSelect($query);
        } else if ($query instanceof InsertQueryQuery) {
            return $this->formatQueryInsert($query);
        }  else if ($query instanceof InsertValuesQuery) {
            return $this->formatValuesInsert($query);
        } else if ($query instanceof Where) {
            return $this->formatWhere($query);
        } else if ($query instanceof RawStatement) {
            return $query->getStatement();
        }

        throw new NotImplementedError();
    }
}
