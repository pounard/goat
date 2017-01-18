<?php

declare(strict_types=1);

namespace Goat\Tests\Driver;

use Goat\Tests\DriverTestCase;

class DatabaseInfoTest extends DriverTestCase
{
    /**
     * Test simple DELETE FROM WHERE
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteWhere($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

        $info = $connection->getDatabaseInfo();
        $name = $connection->getDatabaseName();
        $version = $connection->getDatabaseVersion();

        $this->assertSame($name, $info['name']);
        $this->assertSame($version, $info['version']);

        $currentDriver = $connection->getDriverName();
        $this->assertSame($driver, $currentDriver);
    }
}
