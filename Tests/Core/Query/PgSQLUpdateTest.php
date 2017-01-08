<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class PgSQLUpdateTest extends AbstractUpdateTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
