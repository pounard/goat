<?php

declare(strict_types=1);

namespace Goat\Tests\Core\Client;

use Goat\Core\Client\Dsn;
use Goat\Core\Error\ConfigurationError;

class DsnTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        foreach ([
            // Stupid database names are valid
            'pdo_pgsql:///var/run/pg.sock:coucou@robert_69',
            'unix://pdo_pgsql:///var/run/pg.sock:coucou@robert_69',
        ] as $string) {

            $dsn = new Dsn($string);
            $this->assertSame('pdo_pgsql', $dsn->getDriver());
            $this->assertSame('unix', $dsn->getScheme());
            $this->assertSame('coucou@robert_69', $dsn->getDatabase());
            $this->assertSame('/var/run/pg.sock', $dsn->getHost());
            $this->assertEmpty($dsn->getPort());
            $this->assertTrue($dsn->isUnixSocket());

            $this->assertSame('unix://pdo_pgsql:///var/run/pg.sock:coucou@robert_69', $dsn->formatFull());
            $this->assertSame('unix://pdo_pgsql:///var/run/pg.sock', $dsn->formatWithoutDatabase());
            $this->assertSame('pgsql:unix_socket=/var/run/pg.sock;dbname=coucou@robert_69;client_encoding=utf8', $dsn->formatPdo());
        }

        foreach ([
            // Stupid database names are valid
            'pdo_pgsql://1.2.3.4:1234/`{[@}e#',
            'tcp://pdo_pgsql://1.2.3.4:1234/`{[@}e#',
        ] as $string) {

            $dsn = new Dsn($string);
            $this->assertSame(1234, $dsn->getPort());
            $this->assertSame('1.2.3.4', $dsn->getHost());
            $this->assertSame('`{[@}e#', $dsn->getDatabase());
            $this->assertSame('pdo_pgsql', $dsn->getDriver());
            $this->assertSame('tcp', $dsn->getScheme());
            $this->assertFalse($dsn->isUnixSocket());

            $this->assertSame('pdo_pgsql://1.2.3.4:1234/`{[@}e#', $dsn->formatFull());
            $this->assertSame('pdo_pgsql://1.2.3.4:1234', $dsn->formatWithoutDatabase());
            $this->assertSame('pgsql:host=1.2.3.4;port=1234;dbname=`{[@}e#;client_encoding=utf8', $dsn->formatPdo());
        }

        foreach ([
            'pdo_mysql://robert:666/my_base',
            'tcp://pdo_mysql://robert:666/my_base',
        ] as $string) {

            $dsn = new Dsn($string);
            $this->assertSame(666, $dsn->getPort());
            $this->assertSame('robert', $dsn->getHost());
            $this->assertSame('my_base', $dsn->getDatabase());
            $this->assertSame('pdo_mysql', $dsn->getDriver());
            $this->assertSame('tcp', $dsn->getScheme());
            $this->assertFalse($dsn->isUnixSocket());

            $this->assertSame('pdo_mysql://robert:666/my_base', $dsn->formatFull());
            $this->assertSame('pdo_mysql://robert:666', $dsn->formatWithoutDatabase());
            $this->assertSame('mysql:host=robert;port=666;dbname=my_base;charset=utf8', $dsn->formatPdo());
        }

        foreach ([
            'pdo_mysql:///oupsy_no_host',
            'tcp://pdo_mysql:///oupsy_no_host',
        ] as $string) {

            $dsn = new Dsn($string);
            $this->assertSame(Dsn::DEFAULT_PORT_MYSQL, $dsn->getPort());
            $this->assertSame(Dsn::DEFAULT_HOST, $dsn->getHost());
            $this->assertSame('pdo_mysql', $dsn->getDriver());
            $this->assertSame('tcp', $dsn->getScheme());
            $this->assertSame('oupsy_no_host', $dsn->getDatabase());
            $this->assertFalse($dsn->isUnixSocket());

            $this->assertSame('pdo_mysql://' . Dsn::DEFAULT_HOST . ':' . Dsn::DEFAULT_PORT_MYSQL . '/oupsy_no_host', $dsn->formatFull());
            $this->assertSame('pdo_mysql://' . Dsn::DEFAULT_HOST . ':' . Dsn::DEFAULT_PORT_MYSQL, $dsn->formatWithoutDatabase());
            $this->assertSame('mysql:host=' . Dsn::DEFAULT_HOST . ';port=' . Dsn::DEFAULT_PORT_MYSQL . ';dbname=oupsy_no_host;charset=utf8', $dsn->formatPdo());
        }

        foreach ([
            'pdo_pgsql:///oupsy_no_host',
            'tcp://pdo_pgsql:///oupsy_no_host',
        ] as $string) {

            $dsn = new Dsn($string);
            $this->assertSame(Dsn::DEFAULT_PORT_PGSQL, $dsn->getPort());
            $this->assertSame(Dsn::DEFAULT_HOST, $dsn->getHost());
            $this->assertSame('pdo_pgsql', $dsn->getDriver());
        }

        $invalid = [
            // Unsupported database type
            'oracle://robert:666/my_base',
            'tcp://oracle://robert:666/my_base',
            // 'unix' given, DSN matches 'tcp'
            'unix://pdo_mysql://localhost:1234/my_base',
            'unix://pdo_mysql://1.2.3.4:1234/my_base',
            'unix://pdo_pgsql://1.2.3.4:1234/my_base',
            // 'tcp' given, DSN matches 'unix'
            'tcp://pdo_pgsql:///var/run/pg.sock:some_database',
            'tcp://pgsql:///var/run/pg.sock:some_other',
            // Port without host
            'pdo_mysql://:1234/my_base',
            'pdo_mysql://:1234/my_base',
            'pdo_pgsql://:1234/my_base',
            // Missing database
            'unix://pdo_mysql://localhost:1234',
            'unix://pdo_mysql://1.2.3.4:1234',
            'pdo_pgsql:///var/run/pg.sock',
            'tcp://pdo_pgsql:///var/run/pg.sock',
            // Random ones
            'tcp://localhost',
            'tcp://1.2.3.4:1234',
            'pdo_mysql://1.2.3.4:1234',
            'tcp://1.2.3.4',
            'locahost',
            'locahost:1234',
            'locahost:1234/12',
            '/var/run/mysql.sock',
        ];

        foreach ($invalid as $string) {
            try {
                new Dsn($string);
                $this->fail(sprintf("%s is not supposed to be valid", $string));
            } catch (ConfigurationError $e) {
                $this->assertTrue(true); // Just increment the assertion counter.
            }
        }
    }
}
