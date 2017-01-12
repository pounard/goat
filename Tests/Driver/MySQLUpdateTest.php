<?php

namespace Goat\Tests\Driver;

class MySQLUpdateTest extends AbstractUpdateTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
