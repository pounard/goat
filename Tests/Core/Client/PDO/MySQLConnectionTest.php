<?php

namespace Goat\Tests\Core\Client;

use Goat\Tests\ConnectionAwareTestTrait;
use Goat\Driver\PDO\MySQLConnection;
use Goat\Driver\PDO\DefaultResultIterator;

class MySQLConnectionTest extends \PHPUnit_Framework_TestCase
{
    use ConnectionAwareTestTrait;

    protected function getDriver()
    {
        return 'MYSQL';
    }

    public function testConnection()
    {
        $connection = $this->getConnection();
        $this->assertInstanceOf(MySQLConnection::class, $connection);

        $result = $connection->query("select 1 as one");
        $this->assertInstanceOf(DefaultResultIterator::class, $result);

        $count = 0;
        foreach ($result as $row) {
            $this->assertArrayHasKey('one', $row);
            $this->assertEquals(1, $row['one']);
            $count++;
        }
        $this->assertEquals(1, $count);

        $this->assertFalse($connection->supportsReturning());
    }
}
