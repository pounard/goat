<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class MySQLNamedParametersTest extends AbstractNamedParametersTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
