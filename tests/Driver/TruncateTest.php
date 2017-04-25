<?php

declare(strict_types=1);

namespace Goat\Tests\Driver;

use Goat\Core\Client\ConnectionInterface;
use Goat\Query\Query;
use Goat\Tests\DriverTestCase;

class TruncateTest extends DriverTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(ConnectionInterface $connection)
    {
        $connection->query("
            create temporary table some_table (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp default now(),
                id_user integer
            )
        ");
        $connection->query("
            create temporary table users (
                id serial primary key,
                name varchar(255)
            )
        ");
        $connection->query("
            create temporary table users_status (
                id_user integer,
                status integer
            )
        ");
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(ConnectionInterface $connection)
    {
        $connection
            ->insertValues('users')
            ->columns(['name'])
            ->values(["admin"])
            ->values(["jean"])
            ->execute()
        ;

        $idList = $connection
            ->select('users')
            ->column('id')
            ->orderBy('name')
            ->execute()
            ->fetchColumn()
        ;

        $idAdmin = $idList[0];
        $idJean = $idList[1];

        $connection
            ->insertValues('users_status')
            ->columns(['id_user', 'status'])
            ->values([$idAdmin, 7])
            ->values([$idJean, 11])
            ->values([$idJean, 17])
            ->execute()
        ;

        $connection
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
    public function testTruncateSingle($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $connection->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from users_status")->fetchField());

        $connection->truncateTables('some_table');
        $this->assertSame(0, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $connection->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from users_status")->fetchField());
    }

    /**
     * Test multiple TRUNCATE at once
     *
     * @dataProvider driverDataSource
     */
    public function testTruncateMutltiple($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $connection->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from users_status")->fetchField());

        $connection->truncateTables(['some_table', 'users']);
        $this->assertSame(0, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from users_status")->fetchField());
    }
}
