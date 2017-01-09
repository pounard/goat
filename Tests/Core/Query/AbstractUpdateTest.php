<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Query\Query;
use Goat\Core\Query\Where;
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
            ->column('id')
            ->orderBy('name')
            ->execute()
            ->fetchColumn()
        ;

        $idAdmin = $idList[0];
        $idJean = $idList[1];

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
        $connection = $this->getConnection();

        $result = $connection
            ->update('some_table')
            ->condition('foo', 42)
            ->set('foo', 43)
            ->execute()
        ;

        $this->assertSame(1, $result->countRows());

        $result = $connection
            ->select('some_table')
            ->condition('foo', 43)
            ->execute()
        ;

        $this->assertSame(1, $result->countRows());
        $this->assertSame('a', $result->fetch()['bar']);

        $query = $connection->update('some_table', 'trout');
        $query
            ->where()
            ->open(Where::OR_STATEMENT)
                ->condition('trout.foo', 43)
                ->condition('trout.foo', 666)
            ->close()
        ;

        $result = $query
            ->set('bar', 'cassoulet')
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());
    }

    /**
     * Update using FROM ... JOIN statements
     */
    public function testUpdateJoin()
    {
        $connection = $this->getConnection();

        $result = $connection
            ->update('some_table', 't')
            ->set('foo', 127)
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        $result = $connection
            ->select('some_table', 'roger')
            ->join('users', 'john.id = roger.id_user', 'john')
            ->condition('john.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame(127, $row['foo']);
        }

        $result = $connection
            ->select('some_table')
            ->condition('foo', 127)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
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
