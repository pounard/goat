<?php

namespace Goat\Tests\Driver;

class MySQLTransactionTest extends AbstractTransactionTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
