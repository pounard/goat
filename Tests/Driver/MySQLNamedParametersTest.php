<?php

namespace Goat\Tests\Driver;

class MySQLNamedParametersTest extends AbstractNamedParametersTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
