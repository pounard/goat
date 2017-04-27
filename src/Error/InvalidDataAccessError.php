<?php

declare(strict_types=1);

namespace Goat\Error;

/**
 * User tried to lookup non-fetched data from an iterator
 */
class InvalidDataAccessError extends QueryError
{
}
