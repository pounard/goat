<?php

namespace Goat\Core\Query;

/**
 * Represents a query
 */
interface QueryInterface
{
    /**
     * Is this query a select
     */
    public function isSelect();

    /**
     * Does this query has a projection (does it returns something)
     */
    public function hasProjection();

    /**
     * Get projection, if has any
     *
     * @return Projection
     */
    public function getProjection();

    /**
     * Get where clause
     *
     * @return Where
     */
    public function getWhere();
}
