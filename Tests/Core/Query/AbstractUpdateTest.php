<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Query\Query;
use Goat\Tests\ConnectionAwareTest;

abstract class AbstractUpdateTest extends ConnectionAwareTest
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
            ->columns(['id'])
            ->orderBy('name')
            ->execute()
            ->fetchColumn('id')
        ;

        $idAdmin = $idList[0];
        $idJean = $idList[0];

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
     * Update using simple WHERE conditions
     */
    public function testUpdateWhere()
    {
        $this->markTestIncomplete();
    }

    /**
     * Update using FROM ... JOIN statements
     */
    public function testUpdateJoin()
    {
        $this->markTestIncomplete();
    }

    /**
     * Update using a IN (SELECT ...)
     */
    public function testUpdateWhereIn()
    {
        $this->markTestIncomplete();
    }

    /**
     * Update by using SET column = 'VALUE'
     */
    public function testUpateSetValues()
    {
        $this->markTestIncomplete();
    }

    /**
     * Update RETURNING
     */
    public function testUpateReturning()
    {
        $this->markTestIncomplete();
    }

    /**
     * Update by using SET column = other_table.column from JOIN
     */
    public function testUpateSetReferenceStatement()
    {
        $this->markTestIncomplete();
    }

    /**
     * Update by using SET column = (SELECT ...)
     */
    public function testUpateSetSelectQuery()
    {
        $this->markTestIncomplete();
    }

    /**
     * Update by using SET column = some_statement()
     */
    public function testUpateSetSqlStatement()
    {
        $this->markTestIncomplete();
    }
}
