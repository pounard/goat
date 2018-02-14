<?php

declare(strict_types=1);

namespace Goat\Tests\Driver;

use Goat\Runner\RunnerInterface;
use Goat\Tests\DriverTestCase;

class BinaryObjectTest extends DriverTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(RunnerInterface $driver)
    {
        if (false !== stripos($driver->getDriverName(), 'pgsql')) {
            $driver->query("
                create temporary table storage (
                    id serial primary key,
                    foo bytea
                )
            ");
        } else {
            $driver->query("
                create temporary table storage (
                    id serial primary key,
                    foo blob
                )
            ");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(RunnerInterface $driver)
    {
    }

    /**
     * Very simple test
     *
     * @dataProvider driverDataSource
     */
    public function testInsertAndSelect($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $driver
            ->insertValues('storage')
            ->values([
                'foo' => "åß∂ƒ©˙∆˚¬…æ"
            ])
            ->execute()
        ;

        $value = $driver
            ->select('storage')
            ->column('foo')
            ->execute()
            ->fetchField()
        ;

        $this->assertSame("åß∂ƒ©˙∆˚¬…æ", $value);
    }
}
