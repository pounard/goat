<?php

declare(strict_types=1);

namespace Goat\Error;

/**
 * User query error, it might be one of the following cases:
 *   - a wrong argument has been passed to a query builder function
 *   - a raw SQL query contains invalid SQL
 *   - there is an argument mismatch between a query and passed arguments
 */
class QueryError extends GoatError
{
}
