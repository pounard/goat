<?php

namespace Goat\Tests\Driver;

class PgSQLInsertTest extends AbstractInsertTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
