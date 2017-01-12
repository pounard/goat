<?php

namespace Goat\Tests\Driver;

class PgSQLTruncateTest extends AbstractTruncateTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
