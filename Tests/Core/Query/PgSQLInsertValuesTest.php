<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;

class PgSQLInsertValuesTest extends AbstractInsertValuesTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }
}