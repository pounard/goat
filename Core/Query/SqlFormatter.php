<?php

namespace Goat\Core\Query;

use Goat\Core\Client\EscaperAwareInterface;
use Goat\Core\Client\EscaperAwareTrait;
use Goat\Core\Client\EscaperInterface;
use Goat\Core\Error\NotImplementedError;
use Goat\Core\Error\QueryError;

/**
 * Standard SQL query formatter: this implementation conforms as much as it
 * can to SQL-92 standard, and higher revisions for some functions.
 *
 * Here are a few differences with the SQL standard:
 *
 *  - per default, UPDATE queries allow FROM..JOIN statement, but first JOIN
 *    must be INNER or NATURAL in order to substitute the JOIN per FROM.
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
     * Format a single set clause (update queries)
     *
     * @param string $column
     * @param string|Expression $expression
     *
     * @return string
     */
    protected function formatUpdateSetItem($column, $expression)
    {
        $columnString = $this->escaper->escapeIdentifier($column);

        if ($expression instanceof Expression) {
            return sprintf("%s = %s", $columnString, $this->format($expression));
        } else if ($expression instanceof Statement) {
            return sprintf("%s = (%s)", $columnString, $this->format($expression));
        } else {
            return sprintf("%s = %s", $columnString, $this->escaper->escapeLiteral($expression));
        }
    }

    /**
     * Format all set clauses (update queries)
     *
     * @param string[]|Expression[] $columns
     *   Keys are column names, values are strings or Expression instances
     */
    protected function formatUpdateSet(array $columns)
    {
        $output = [];

        foreach ($columns as $column => $statement) {
            $output[] = $this->formatUpdateSetItem($column, $statement);
        }

        return implode(",\n", $output);
    }

    /**
     * Format projection for a single select column or statement
     *
     * @param string|Expression $statement
     * @param string $alias
     *
     * @return string
     */
    protected function formatSelectItem($expression, $alias = null)
    {
        if (is_string($expression)) {
            $expression = new ExpressionColumn($expression);
        }

        $output = $this->format($expression);

        // We cannot alias columns with a numeric identifier;
        // aliasing with the same string as the column name
        // makes no sense either.
        if ($alias && !is_numeric($alias)) {
            $alias = $this->escaper->escapeIdentifier($alias);
            if ($alias !== $output) {
                return $output . ' as ' . $alias;
            }
        }

        return $output;
    }

    /**
     * Format the whole projection
     *
     * @param array $columns
     *   Each column is an array that must contain:
     *     - 0: string or Statement: column name or SQL statement
     *     - 1: column alias, can be empty or null for no aliasing
     *
     * @return string
     */
    protected function formatSelect(array $columns)
    {
        if (!$columns) {
            return '*';
        }

        $output = [];

        foreach ($columns as $column) {
            $output[] = $this->formatSelectItem(...$column);
        }

        return implode(",\n", $output);
    }

    /**
     * Format projection for a single returning column or statement
     *
     * @param string|Expression $statement
     * @param string $alias
     *
     * @return string
     */
    protected function formatReturningItem($expression, $alias = null)
    {
        return $this->formatSelectItem($expression, $alias);
    }

    /**
     * Format the whole projection
     *
     * @param array $return
     *   Each column is an array that must contain:
     *     - 0: string or Statement: column name or SQL statement
     *     - 1: column alias, can be empty or null for no aliasing
     *
     * @return string
     */
    protected function formatReturning(array $return)
    {
        return $this->formatSelect($return);
    }

    /**
     * Format a single order by
     *
     * @param string|Expression $column
     * @param int $order
     *   Query::ORDER_* constant
     * @param int $null
     *   Query::NULL_* constant
     */
    protected function formatOrderByItem($column, $order, $null)
    {
        $column = $this->format($column);

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
     * Format the whole order by clause
     *
     * @param $orders
     *   Each order is an array that must contain:
     *     - 0: Expression
     *     - 1: Query::ORDER_* constant
     *     - 2: Query::NULL_* constant
     *
     * @return string
     */
    protected function formatOrderBy(array $orders)
    {
        if (!$orders) {
            return '';
        }

        $output = [];

        foreach ($orders as $data) {
            list($column, $order, $null) = $data;
            $output[] = $this->formatOrderByItem($column, $order, $null);
        }

        return "order by " . implode(", ", $output);
    }

    /**
     * Format the whole group by clause
     *
     * @param Expression[] $groups
     *   Array of column names or aliases
     *
     * @return string
     */
    protected function formatGroupBy(array $groups)
    {
        if (!$groups) {
            return '';
        }

        $output = [];
        foreach ($groups as $group) {
            $output[] = $this->format($group);
        }

        return "group by " . implode(", ", $output);
    }

    /**
     * Format a single join statement
     *
     * @param ExpressionRelation $relation
     *   Relation to join on name
     * @param Where $condition
     *   There where condition to join upon, it can be empty or null case in
     *   which this will leave the SQL engine doing a cartesian product with
     *   the tables
     * @param int $mode
     *   Query::JOIN_* constant
     *
     * @return string
     */
    protected function formatJoinItem(ExpressionRelation $relation, Where $condition, $mode)
    {
        switch ($mode) {

            case Query::JOIN_NATURAL:
                $prefix = 'natural join';
                break;

            case Query::JOIN_LEFT:
            case Query::JOIN_LEFT_OUTER:
                $prefix = 'left outer join';
                break;

            case Query::JOIN_RIGHT:
            case Query::JOIN_RIGHT_OUTER:
                $prefix = 'right outer join';
                break;

            case Query::JOIN_INNER:
            default:
                $prefix = 'inner join';
                break;
        }

        if ($condition->isEmpty()) {
            return sprintf(
                "%s %s",
                $prefix,
                $this->formatExpressionRelation($relation)
            );
        } else {
            return sprintf(
                "%s %s on (%s)",
                $prefix,
                $this->formatExpressionRelation($relation),
                $this->formatWhere($condition)
            );
        }
    }

    /**
     * Format all join statements
     *
     * @param array $joins
     *   Each join is an array that must contain:
     *     - key must be the relation alias
     *     - 0: ExpressionRelation relation name
     *     - 1: Where or null condition
     *     - 2: Query::JOIN_* constant
     *
     * @return string
     */
    protected function formatJoin(array $joins)
    {
        if (!$joins) {
            return '';
        }

        $output = [];

        foreach ($joins as $join) {
            $output[] = $this->formatJoinItem(...$join);
        }

        return implode("\n", $output);
    }

    /**
     * Format all update from statement
     *
     * @param array $relations
     *   Each relation is an array that must contain:
     *     - key must be the relation alias
     *     - 0: ExpressionRelation relation name
     *     - 1: Where or null condition
     *     - 2: Query::JOIN_* constant
     *
     * @return string
     */
    protected function formatUpdateFrom(UpdateQuery $query, array $joins)
    {
        if (!$joins) {
            return '';
        }

        $output = [];

        $first = array_shift($joins);

        // First join must be an inner join, there is no choice, and first join
        // condition will become a where clause in the global query instead
        if (!in_array($first[2], [Query::JOIN_INNER, Query::JOIN_NATURAL])) {
            throw new QueryError("first join in an update query must be inner or natural, it will serve as the first from table");
        }

        $output[] = sprintf("from %s", $this->formatExpressionRelation($first[0]));
        if ($first[1] && !$first[1]->isEmpty()) {
            $query->where()->expression($first[1]);
        }

        // Format remaining joins normally, most database servers can do that
        // at least PostgreSQL and SQLServer do
        if ($joins) {
            foreach ($joins as $join) {
                $output[] = $this->formatJoinItem(...$join);
            }
        }

        return implode("\n", $output);
    }

    /**
     * Format range statement
     *
     * @param int $limit
     *   O means no limit
     * @param int $offset
     *   0 means default offset
     *
     * @return string
     */
    protected function formatRange($limit = 0, $offset = 0)
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
     * Format value list
     *
     * @param mixed[] $arguments
     *   Arbitrary arguments
     * @param string $type = null
     *   Data type of arguments
     *
     * @return string
     */
    protected function formatValueList($arguments)
    {
        return implode(
            ', ',
            array_map(
                function ($value) {
                    if ($value instanceof Statement) {
                        return $this->format($value);
                    } else {
                        return '$*';
                    }
                },
                $arguments
            )
        );
    }

    /**
     * Format placeholder for a single value
     *
     * @param string $argument
     */
    protected function formatPlaceholder($argument)
    {
        return '$*';
    }

    /**
     * Format where instance
     *
     * @param Where $where
     *
     * @return string
     */
    protected function formatWhere(Where $where)
    {
        if ($where->isEmpty()) {
            // Definitely legit (except for pgsql which awaits a boolean)
            return '1';
        }

        $output = [];

        foreach ($where->getConditions() as $condition) {
            list($column, $value, $operator) = $condition;

            // Do not allow an empty where to be displayed
            if ($value instanceof Where && $value->isEmpty()) {
                continue;
            }

            $columnString = '';
            $valueString = '';

            if ($column) {
                $columnString = $this->format($column);
            }

            if ($value instanceof Expression) {
                $valueString = $this->format($value);
            } else if ($value instanceof Statement) {
                $valueString = sprintf('(%s)', $this->format($value));
            } else if (is_array($value)) {
                $valueString = sprintf("(%s)", $this->formatValueList($value));
            } else {
                $valueString = $this->formatPlaceholder($value);
            }

            if (!$column) {
                switch ($operator) {

                    case Where::EXISTS:
                    case Where::NOT_EXISTS:
                        $output[] = sprintf('%s %s', $operator, $valueString);
                        break;

                    case Where::IS_NULL:
                    case Where::NOT_IS_NULL:
                        $output[] = sprintf('%s %s', $valueString, $operator);
                        break;

                    default:
                       $output[] = $valueString;
                       break;
                }
            } else {
                switch ($operator) {

                    case Where::EXISTS:
                    case Where::NOT_EXISTS:
                        $output[] = sprintf('%s %s', $operator, $valueString);
                        break;

                    case Where::IS_NULL:
                    case Where::NOT_IS_NULL:
                        $output[] = sprintf('%s %s', $columnString, $operator);
                        break;

                    case Where::BETWEEN:
                    case Where::NOT_BETWEEN:
                        $output[] = sprintf('%s %s $* and $*', $columnString, $operator);
                        break;

                    default:
                        $output[] = sprintf('%s %s %s', $columnString, $operator, $valueString);
                        break;
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
    protected function formatQueryInsertValues(InsertValuesQuery $query)
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
            // From SQL 92 standard, INSERT queries don't have table alias
            $this->escaper->escapeIdentifier($query->getRelation()->getRelation())
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
            $output[] = sprintf("returning %s", $this->formatReturning($return));
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
    protected function formatQueryInsertFrom(InsertQueryQuery $query)
    {
        $output = [];

        $escaper = $this->escaper;
        $columns = $query->getAllColumns();
        $subQuery = $query->getQuery();

        $output[] = sprintf(
            "insert into %s",
            // From SQL 92 standard, INSERT queries don't have table alias
            $this->escaper->escapeIdentifier($query->getRelation()->getRelation())
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
            $output[] = sprintf("returning %s", $this->formatReturning($return));
        }

        return implode("\n", $output);
    }

    /**
     * Format given update query
     *
     * @param UpdateQuery $query
     *
     * @return string
     */
    protected function formatQueryUpdate(UpdateQuery $query)
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryError("cannot run an update query without any columns to update");
        }

        // From the SQL 92 standard (which PostgreSQL does support here) the
        // FROM and JOIN must be written AFTER the SET clause. MySQL does not.
        $output[] = sprintf(
            "update %s\nset\n%s\n%s",
            $this->formatExpressionRelation($query->getRelation()),
            $this->formatUpdateSet($columns),
            $this->formatUpdateFrom($query, $query->getAllJoin())
        );

        $where = $query->where();
        if (!$where->isEmpty()) {
            $output[] = sprintf('where %s', $this->formatWhere($where));
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = sprintf("returning %s", $this->formatReturning($return));
        }

        return implode("\n", array_filter($output));
    }

    /**
     * Format given select query
     *
     * @param SelectQuery $query
     *
     * @return string
     */
    protected function formatQuerySelect(SelectQuery $query)
    {
        $output = [];
        $output[] = sprintf(
            "select %s\nfrom %s\n%s",
            $this->formatSelect($query->getAllColumns()),
            $this->formatExpressionRelation($query->getRelation()),
            $this->formatJoin($query->getAllJoin())
        );

        $where = $query->where();
        if (!$where->isEmpty()) {
            $output[] = sprintf('where %s', $this->formatWhere($where));
        }

        $output[] = $this->formatGroupBy($query->getAllGroupBy());
        $output[] = $this->formatOrderBy($query->getAllOrderBy());
        $output[] = $this->formatRange(...$query->getRange());

        $having = $query->having();
        if (!$having->isEmpty()) {
            $output[] = sprintf('having %s', $this->formatWhere($having));
        }

        return implode("\n", array_filter($output));
    }

    /**
     * Format value expression
     *
     * @param ExpressionValue $value
     *
     * @return string
     */
    protected function formatExpressionRaw(ExpressionRaw $expression)
    {
        return $expression->getExpression();
    }

    /**
     * Format value expression
     *
     * @param ExpressionValue $value
     *
     * @return string
     */
    protected function formatExpressionColumn(ExpressionColumn $column)
    {
        $relation = $column->getRelation();

        $target = $column->getColumn();
        // Allow selection such as "table".*
        if ('*' !== $target) {
            $target = $this->escaper->escapeIdentifier($target);
        }

        if ($relation) {
            return sprintf(
                "%s.%s",
                $this->escaper->escapeIdentifier($relation),
                $target
            );
        } else {
            return $target;
        }
    }

    /**
     * Format relation expression
     *
     * @param ExpressionRelation $value
     *
     * @return string
     */
    protected function formatExpressionRelation(ExpressionRelation $relation)
    {
        $table  = $relation->getRelation();
        $schema = $relation->getSchema();
        $alias  = $relation->getAlias();

        if ($alias === $table) {
            $alias = null;
        }

        if ($schema && $alias) {
            return sprintf(
                "%s.%s as %s",
                $this->escaper->escapeIdentifier($schema),
                $this->escaper->escapeIdentifier($table),
                $this->escaper->escapeIdentifier($alias)
            );
        } else if ($schema) {
            return sprintf(
                "%s.%s",
                $this->escaper->escapeIdentifier($schema),
                $this->escaper->escapeIdentifier($table)
            );
        } else if ($alias) {
            return sprintf(
                "%s as %s",
                $this->escaper->escapeIdentifier($table),
                $this->escaper->escapeIdentifier($alias)
            );
        } else {
            return sprintf(
                "%s",
                $this->escaper->escapeIdentifier($table)
            );
        }
    }

    /**
     * Format value expression
     *
     * @param ExpressionValue $value
     *
     * @return string
     */
    protected function formatExpressionValue(ExpressionValue $value)
    {
        return $this->formatPlaceholder($value->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function format($query)
    {
        if ($query instanceof ExpressionColumn) {
            return $this->formatExpressionColumn($query);
        } else if ($query instanceof ExpressionRaw) {
            return $this->formatExpressionRaw($query);
        } else if ($query instanceof ExpressionRelation) {
            return $this->formatExpressionRelation($query);
        } else if ($query instanceof ExpressionValue) {
            return $this->formatExpressionValue($query);
        } else if ($query instanceof SelectQuery) {
            return $this->formatQuerySelect($query);
        } else if ($query instanceof InsertQueryQuery) {
            return $this->formatQueryInsertFrom($query);
        } else if ($query instanceof InsertValuesQuery) {
            return $this->formatQueryInsertValues($query);
        } else if ($query instanceof UpdateQuery) {
            return $this->formatQueryUpdate($query);
        } else if ($query instanceof Where) {
            return $this->formatWhere($query);
        }

        throw new NotImplementedError();
    }
}
