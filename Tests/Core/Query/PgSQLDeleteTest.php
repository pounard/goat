<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class PgSQLDeleteTest extends AbstractDeleteTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
