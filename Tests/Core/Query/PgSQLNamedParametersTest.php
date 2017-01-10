<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class PgSQLNamedParametersTest extends AbstractNamedParametersTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
