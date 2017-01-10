<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentHolderInterface;

/**
 * A statement is something that the SQL database can execute. It does not
 * always return values, but it can return multiple values.
 */
interface Statement extends ArgumentHolderInterface
{
}
