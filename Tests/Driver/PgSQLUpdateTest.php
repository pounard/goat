<?php

namespace Goat\Tests\Driver;

class PgSQLUpdateTest extends AbstractUpdateTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
