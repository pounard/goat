<?php

namespace Goat\Tests\Core\Client;

class PgSQLTruncateTest extends AbstractTruncateTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}
