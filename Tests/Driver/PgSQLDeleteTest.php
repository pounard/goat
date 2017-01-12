<?php

namespace Goat\Tests\Driver;

class PgSQLDeleteTest extends AbstractDeleteTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
