<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Error\QueryError;
use Goat\Query\Expression;
use Goat\Query\Where;

trait MapperTrait
{
    /**
     * Build where from criteria
     *
     * @param array|Expression|Where $criteria
     *   This value might be either one of:
     *     - a simple key-value array that will be translated into a where
     *       clause using the AND statement, values can be anything including
     *       Expression or Where instances, if keys are integers, values must
     *       will be set using Where::expression() instead of Where::condition()
     *     - a Expression instance
     *     - an array of Expression instances
     *     - a Where instance
     *
     * @return Where
     */
    protected function createWhereWith($criteria) : Where
    {
        if (!$criteria) {
            return new Where();
        }
        if ($criteria instanceof Where) {
            return $criteria;
        }
        if ($criteria instanceof Expression) {
            return (new Where())->expression($criteria);
        }

        if (is_array($criteria)) {
            $where = new Where();

            foreach ($criteria as $column => $value) {
                if (is_int($column)) {
                    $where->expression($value);
                } else if (is_null($value)) {
                    $where->isNull($column);
                } else {
                    $where->condition($column, $value);
                }
            }

            return $where;
        }

        throw new QueryError("criteria must be an instance of Where, Expression, or an key-value pairs array where keys are columns names and values are column value");
    }
}
