<?php

namespace Goat\Tests\Driver;

class MySQLInsertTest extends AbstractInsertTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
