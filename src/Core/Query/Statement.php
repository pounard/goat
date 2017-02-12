<?php

declare(strict_types=1);

namespace Goat\Core\Query;

use Goat\Core\Query\ArgumentHolderInterface;

/**
 * A statement is something that the SQL database can execute. It does not
 * always return values, but it can return multiple values.
 */
interface Statement extends ArgumentHolderInterface
{
}
