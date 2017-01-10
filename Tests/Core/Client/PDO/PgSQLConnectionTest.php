<?php

namespace Goat\Tests\Core\Client;

use Goat\Driver\PDO\DefaultResultIterator;
use Goat\Driver\PDO\PgSQLConnection;
use Goat\Tests\ConnectionAwareTest;

class PgSQLConnectionTest extends ConnectionAwareTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }

    public function testConnection()
    {
        $connection = $this->getConnection();

        $result = $connection->query("select 1 as one");
        $this->assertInstanceOf(DefaultResultIterator::class, $result);

        $count = 0;
        foreach ($result as $row) {
            $this->assertArrayHasKey('one', $row);
            $this->assertEquals(1, $row['one']);
            $count++;
        }
        $this->assertEquals(1, $count);

        $this->assertTrue($connection->supportsReturning());
    }
}
