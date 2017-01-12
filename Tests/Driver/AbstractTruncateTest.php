<?php

namespace Goat\Tests\Driver;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Query\Query;
use Goat\Tests\ConnectionAwareTest;

abstract class AbstractTruncateTest extends ConnectionAwareTest
{
    private $idAdmin;
    private $idJean;

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

        $this->idAdmin = $idList[0];
        $this->idJean = $idList[1];

        $connection
            ->insertValues('users_status')
            ->columns(['id_user', 'status'])
            ->values([$this->idAdmin, 7])
            ->values([$this->idJean, 11])
            ->values([$this->idJean, 17])
            ->execute()
        ;

        $connection
            ->insertValues('some_table')
            ->columns(['foo', 'bar', 'id_user'])
            ->values([42, 'a', $this->idAdmin])
            ->values([666, 'b', $this->idAdmin])
            ->values([37, 'c', $this->idJean])
            ->values([11, 'd', $this->idJean])
            ->values([27, 'e', $this->idAdmin])
            ->execute()
        ;
    }

    /**
     * Test single TRUNCATE
     */
    public function testTruncateSingle()
    {
        $connection = $this->getConnection();
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
     */
    public function testTrunateMutltiple()
    {
        $connection = $this->getConnection();
        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $connection->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from users_status")->fetchField());

        $connection->truncateTables(['some_table', 'users']);
        $this->assertSame(0, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from users")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from users_status")->fetchField());
    }
}
