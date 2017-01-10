<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Query\Query;
use Goat\Core\Query\Where;
use Goat\Tests\ConnectionAwareTest;
use Goat\Core\Query\ExpressionColumn;
use Goat\Core\Query\ExpressionRaw;

abstract class AbstractUpdateTest extends ConnectionAwareTest
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
            ->open(Where::OR)
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

        // All code below is just consistency checks
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
        $connection = $this->getConnection();

        $selectInQuery = $connection
            ->select('users')
            ->column('id')
            ->condition('name', 'admin')
        ;

        $result = $connection
            ->update('some_table', 't')
            ->set('foo', 127)
            ->condition('t.id_user', $selectInQuery)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
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
     * Update RETURNING
     */
    public function testUpateReturning()
    {
        $connection = $this->getConnection();

        if (!$connection->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $result = $connection
            ->update('some_table', 't')
            ->set('foo', 127)
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->returning(new ExpressionRaw('*'))
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame(127, $row['foo']);
            $this->assertSame('admin', $row['name']);
        }
    }

    /**
     * Update by using SET column = other_table.column from FROM using ExpressionColumn
     */
    public function testUpateSetExpressionColumn()
    {
        $connection = $this->getConnection();

        $result = $connection
            ->update('some_table', 't')
            ->set('foo', new ExpressionColumn('u.id'))
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $connection
            ->select('some_table', 't')
            ->columns(['t.foo', 't.id_user'])
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame($row['id_user'], $row['foo']);
        }
    }

    /**
     * Update by using SET column = other_table.column from FROM using ExpressionRaw
     */
    public function testUpateSetExpressionRaw()
    {
        $connection = $this->getConnection();

        $result = $connection
            ->update('some_table', 't')
            ->set('foo', new ExpressionRaw('u.id'))
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $connection
            ->select('some_table', 't')
            ->columns(['t.foo', 't.id_user'])
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame($row['id_user'], $row['foo']);
        }
    }

    /**
     * Update by using SET column = (SELECT ...)
     */
    public function testUpateSetSelectQuery()
    {
        $connection = $this->getConnection();

        $selectValueQuery = $connection
            ->select('users', 'z')
            ->columnExpression('z.id + 7')
            ->expression('z.id = id_user')
        ;

        $result = $connection
            ->update('some_table')
            ->set('foo', $selectValueQuery)
            ->condition('id_user', $this->idJean)
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());

        $result = $connection
            ->select('some_table')
            ->condition('id_user', $this->idJean)
            ->execute()
        ;
        foreach ($result as $row) {
            $this->assertSame($row['id_user'] + 7, $row['foo']);
        }

        $result = $connection
            ->select('some_table')
            ->condition('id_user', $this->idAdmin)
            ->execute()
        ;
        foreach ($result as $row) {
            $this->assertNotSame($row['id_user'] + 7, $row['foo']);
        }
    }

    /**
     * Update by using SET column = some_statement()
     */
    public function testUpateSetSqlStatement()
    {
        $connection = $this->getConnection();

        $result = $connection
            ->update('some_table')
            ->set('foo', new ExpressionRaw('id_user * 2'))
            ->join('users', 'u.id = id_user', 'u')
            ->condition('id_user', $this->idJean)
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());
    }
}
