<?php

namespace Goat\Tests\Driver;

class PgSQLNamedParametersTest extends AbstractNamedParametersTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
