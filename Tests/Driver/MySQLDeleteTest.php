<?php

namespace Goat\Tests\Driver;

class MySQLDeleteTest extends AbstractDeleteTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
