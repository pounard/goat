<?php

namespace Goat\Tests\Core\Client;

class MySQLTruncateTest extends AbstractTruncateTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
