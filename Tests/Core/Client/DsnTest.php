<?php

namespace MakinaCorpus\RedisBundle\Tests;

use Momm\Core\Client\Dsn;

class DsnTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        // Valid cases
        $dsn = new Dsn('tcp://localhost:1234/my_base');
        $this->assertFalse($dsn->isUnixSocket());
        $this->assertSame('tcp://localhost:1234/my_base', $dsn->formatFull());
        $this->assertSame('tcp://localhost:1234', $dsn->formatWithoutDatabase());
        $this->assertSame('mysql:host=localhost;port=1234;dbname=my_base;charset=utf8', $dsn->formatPdo());

        $dsn = new Dsn('tcp://localhost:1234/my_base');
        $dsn = new Dsn('tcp://1.2.3.4:1234/my_base');
        $dsn = new Dsn('mysql://1.2.3.4:1234/my_base');

        $dsn = new Dsn('mysql://1.2.3.4/my_base');
        $this->assertFalse($dsn->isUnixSocket());
        $this->assertSame('tcp://1.2.3.4:3306/my_base', $dsn->formatFull());
        $this->assertSame('tcp://1.2.3.4:3306', $dsn->formatWithoutDatabase());
        $this->assertSame('mysql:host=1.2.3.4;port=3306;dbname=my_base;charset=utf8', $dsn->formatPdo());

        $dsn = new Dsn('unix:///var/run/mysql.sock:my_db');
        $this->assertTrue($dsn->isUnixSocket());
        $this->assertSame('unix:///var/run/mysql.sock:my_db', $dsn->formatFull());
        $this->assertSame('unix:///var/run/mysql.sock', $dsn->formatWithoutDatabase());
        $this->assertSame('mysql:unix_socket=/var/run/mysql.sock;dbname=my_db;charset=utf8', $dsn->formatPdo());

        // Failing cases
        $failing = [
            // Missing database for the next 4
            'tcp://localhost',
            'tcp://1.2.3.4:1234',
            'mysql://1.2.3.4:1234',
            'tcp://1.2.3.4',
            'locahost',
            'locahost:1234',
            'locahost:1234/12',
            '/var/run/mysql.sock',
        ];
        foreach ($failing as $string) {
            try {
                new Dsn($string);
                $this->fail(sprintf("%s: dsn is supposed to be invalid", $string));
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }
}
