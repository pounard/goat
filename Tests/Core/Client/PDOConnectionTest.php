<?php

namespace Goat\Tests\Core\Client;

use Goat\Core\Client\PDO\PDOConnection;
use Goat\Core\Converter\Converter;

class PDOConnectionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (!getenv('MYSQL_DSN')) {
            $this->markTestSkipped("Please set-up the MYSQL_DSN environment variable");
        }
    }

    public function testConnection()
    {
        $connection = new PDOConnection(getenv('MYSQL_DSN'), getenv('MYSQL_USERNAME'), getenv('MYSQL_PASSWORD'));
        $connection->setConverter(new Converter());

        $result = $connection->query("select 1 as one");
        $count = 0;
        foreach ($result as $row) {
            $this->assertArrayHasKey('one', $row);
            $this->assertEquals(1, $row['one']);
            $count++;
        }
        $this->assertEquals(1, $count);
    }
}
