<?php

declare(strict_types=1);

namespace Goat\Tests\Driver;

use Goat\Query\Query;
use Goat\Runner\RunnerInterface;
use Goat\Tests\DriverTestCase;

class TruncateTest extends DriverTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(RunnerInterface $driver)
    {
        $driver->query("
            create temporary table some_table (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp default now(),
                id_user integer
            )
        ");
        $driver->query("
            create temporary table users (
                id serial primary key,
                name varchar(255)
            )
        ");
        $driver->query("
            create temporary table users_status (
                id_user integer,
                status integer
            )
        ");
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(RunnerInterface $driver)
    {
        $driver
            ->insertValues('users')
            ->columns(['name'])
            ->values(["admin"])
            ->values(["jean"])
            ->execute()
        ;

        $idList = $driver
            ->select('users')
            ->column('id')
            ->orderBy('name')
            ->execute()
            ->fetchColumn()
        ;

        $idAdmin = $idList[0];
        $idJean = $idList[1];

        $driver
            ->insertValues('users_status')
            ->columns(['id_user', 'status'])
            ->values([$idAdmin, 7])
            ->values([$idJean, 11])
            ->values([$idJean, 17])
            ->execute()
        ;

        $driver
            ->insertValues('some_table')
            ->columns(['foo', 'bar', 'id_user'])
            ->values([42, 'a', $idAdmin])
            ->values([666, 'b', $idAdmin])
            ->values([37, 'c', $idJean])
            ->values([11, 'd', $idJean])
            ->values([27, 'e', $idAdmin])
            ->execute()
        ;
    }

    /**
     * Test single TRUNCATE
     *
     * @dataProvider driverDataSource
     */
    public function testTruncateSingle($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $driver->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from users_status")->fetchField());

        $driver->truncateTables('some_table');
        $this->assertSame(0, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $driver->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from users_status")->fetchField());
    }

    /**
     * Test multiple TRUNCATE at once
     *
     * @dataProvider driverDataSource
     */
    public function testTruncateMutltiple($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $driver->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from users_status")->fetchField());

        $driver->truncateTables(['some_table', 'users']);
        $this->assertSame(0, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $driver->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from users_status")->fetchField());
    }
}
