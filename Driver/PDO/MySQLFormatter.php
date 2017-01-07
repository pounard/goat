<?php

namespace Goat\Driver\PDO;

use Goat\Core\Error\QueryError;
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
    protected function formatUpdate(UpdateQuery $query)
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryError("cannot run an update query without any columns to update");
        }

        // From the SQL 92 standard (which PostgreSQL does support here) the
        // FROM and JOIN must be written AFTER the SET clause. MySQL does not.
        $output[] = sprintf(
            "update %s %s\n%s\nset\n%s",
            $this->escaper->escapeIdentifier($query->getRelation()),
            $this->escaper->escapeIdentifier($query->getRelationAlias()),
            $this->formatJoinAll($query->getAllJoin()),
            $this->formatSetClauseAll($columns)
        );

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = sprintf("returning %s", $this->formatReturningAll($return));
        }

        return implode("\n", $output);
    }
}
