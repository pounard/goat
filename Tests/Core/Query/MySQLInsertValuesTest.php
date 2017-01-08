<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class MySQLInsertValuesTest extends AbstractInsertValuesTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
