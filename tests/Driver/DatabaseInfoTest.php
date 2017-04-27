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
    public function testDatabaseInfo($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        $info = $driver->getDatabaseInfo();
        $name = $driver->getDatabaseName();
        $version = $driver->getDatabaseVersion();

        $this->assertSame($name, $info['name']);
        $this->assertNotEmpty($name);
        $this->assertSame($version, $info['version']);

        $currentDriver = $driver->getDriverName();
        $this->assertSame($driverName, $currentDriver);
    }
}
