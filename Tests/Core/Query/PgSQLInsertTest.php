<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class PgSQLInsertTest extends AbstractInsertTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
