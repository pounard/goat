<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class MySQLUpdateTest extends AbstractUpdateTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
