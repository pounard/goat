<?php

namespace Goat\Tests\Driver;

class MySQLTruncateTest extends AbstractTruncateTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
