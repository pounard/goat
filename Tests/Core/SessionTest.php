<?php

namespace Goat\Tests\Core;

use Goat\Core\Session;
use Goat\Driver\PDO\PDOConnection;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (!getenv('MYSQL_DSN')) {
            $this->markTestSkipped("Please set-up the MYSQL_DSN environment variable");
        }
    }

    public function testSessionWithArray()
    {
        $session = new Session(['dsn' => getenv('MYSQL_DSN'), 'username' => getenv('MYSQL_USERNAME'), 'password' => getenv('MYSQL_PASSWORD')]);

        $result = $session->getConnection()->query("select 1 as one");
        $count = 0;
        foreach ($result as $row) {
            $this->assertArrayHasKey('one', $row);
            $this->assertEquals(1, $row['one']);
            $count++;
        }
        $this->assertEquals(1, $count);
    }

    public function testSessionWithConnection()
    {
        $session = new Session(new PDOConnection(getenv('MYSQL_DSN'), getenv('MYSQL_USERNAME'), getenv('MYSQL_PASSWORD')));

        $result = $session->getConnection()->query("select 1 as one");
        $count = 0;
        foreach ($result as $row) {
            $this->assertArrayHasKey('one', $row);
            $this->assertEquals(1, $row['one']);
            $count++;
        }
        $this->assertEquals(1, $count);
    }
}
