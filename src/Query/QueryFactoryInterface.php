<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Creates queries and transactions.
 */
interface QueryFactoryInterface
{
    /**
     * Create a select query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return SelectQuery
     */
    public function select($relation, string $alias = null) : SelectQuery;

    /**
     * Create an update query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return UpdateQuery
     */
    public function update($relation, string $alias = null) : UpdateQuery;

    /**
     * Create an insert query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     *
     * @return InsertValuesQuery
     */
    public function insertValues($relation) : InsertValuesQuery;

    /**
     * Create an insert with query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     *
     * @return InsertQueryQuery
     */
    public function insertQuery($relation) : InsertQueryQuery;

    /**
     * Create a delete query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     *
     * @return DeleteQuery
     */
    public function delete($relation, string $alias = null) : DeleteQuery;
}
