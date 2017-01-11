<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class MySQLDeleteTest extends AbstractDeleteTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
