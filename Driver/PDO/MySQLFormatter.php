<?php

namespace Goat\Driver\PDO;

use Goat\Core\Error\QueryError;
use Goat\Core\Query\DeleteQuery;
use Goat\Core\Query\SqlFormatter;
use Goat\Core\Query\UpdateQuery;

/**
 * Standard SQL query formatter
 */
class MySQLFormatter extends SqlFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function formatQueryDelete(DeleteQuery $query) : string
    {
        $output = [];

        // MySQL need to specify on which table to delete from if there is an
        // alias on the main table, so we are going to give him this always
        // so we won't have to bother about weither or not we have other tables
        // to JOIN.
        $relation = $query->getRelation();
        $relationAlias = $relation->getAlias();
        if (!$relationAlias) {
            $relationAlias = $relation->getRelation();
        }

        $output[] = sprintf(
            "delete %s.* from %s",
            $this->escaper->escapeIdentifier($relationAlias),
            $this->formatExpressionRelation($relation)
        );

        // MySQL does not have USING clause, and support a non-standard way of
        // writing DELETE directly using FROM .. JOIN clauses, just like you
        // would write a SELECT, so give him that.
        $joins = $query->getAllJoin();
        if ($joins) {
            $output[] = $this->formatJoin($joins);
        }

        $where = $query->where();
        if (!$where->isEmpty()) {
            $output[] = sprintf('where %s', $this->formatWhere($where));
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryError("MySQL does not support RETURNING SQL clause");
        }

        return implode("\n", array_filter($output));
    }

    /**
     * {@inheritdoc}
     */
    protected function formatQueryUpdate(UpdateQuery $query) : string
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryError("cannot run an update query without any columns to update");
        }

        // From the SQL 92 standard (which PostgreSQL does support here) the
        // FROM and JOIN must be written AFTER the SET clause. MySQL does not.
        $output[] = sprintf("update %s", $this->formatExpressionRelation($query->getRelation()));

        // MySQL don't do UPDATE t1 SET [...] FROM t2 but uses the SELECT
        // syntax and just append the set after the JOIN clause.
        $joins = $query->getAllJoin();
        if ($joins) {
            $output[] = $this->formatJoin($query->getAllJoin());
        }

        // SET clause.
        $output[] = sprintf("set\n%s", $this->formatUpdateSet($columns));

        $where = $query->where();
        if (!$where->isEmpty()) {
            $output[] = sprintf('where %s', $this->formatWhere($where));
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryError("MySQL does not support RETURNING SQL clause");
        }

        return implode("\n", $output);
    }
}
