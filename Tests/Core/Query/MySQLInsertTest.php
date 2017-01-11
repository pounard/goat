<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class MySQLInsertTest extends AbstractInsertTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
