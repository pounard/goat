<?php

declare(strict_types=1);

namespace Goat\Tests\Driver;

use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRaw;
use Goat\Query\Query;
use Goat\Query\Where;
use Goat\Runner\RunnerInterface;
use Goat\Tests\DriverTestCase;

class UpdateTest extends DriverTestCase
{
    private $idAdmin;
    private $idJean;

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

        $this->idAdmin = $idList[0];
        $this->idJean = $idList[1];

        $driver
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
     *
     * @dataProvider driverDataSource
     */
    public function testUpdateWhere($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $result = $driver
            ->update('some_table')
            ->condition('foo', 42)
            ->set('foo', 43)
            ->execute()
        ;

        $this->assertSame(1, $result->countRows());

        $result = $driver
            ->select('some_table')
            ->condition('foo', 43)
            ->execute()
        ;

        $this->assertSame(1, $result->countRows());
        $this->assertSame('a', $result->fetch()['bar']);

        $query = $driver->update('some_table', 'trout');
        $query
            ->getWhere()
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
     *
     * @dataProvider driverDataSource
     */
    public function testUpdateJoin($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $result = $driver
            ->update('some_table', 't')
            ->set('foo', 127)
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $driver
            ->select('some_table', 'roger')
            ->join('users', 'john.id = roger.id_user', 'john')
            ->condition('john.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame(127, $row['foo']);
        }

        $result = $driver
            ->select('some_table')
            ->condition('foo', 127)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
    }

    /**
     * Update using a IN (SELECT ...)
     *
     * @dataProvider driverDataSource
     */
    public function testUpdateWhereIn($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $selectInQuery = $driver
            ->select('users')
            ->column('id')
            ->condition('name', 'admin')
        ;

        $result = $driver
            ->update('some_table', 't')
            ->set('foo', 127)
            ->condition('t.id_user', $selectInQuery)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $driver
            ->select('some_table', 'roger')
            ->join('users', 'john.id = roger.id_user', 'john')
            ->condition('john.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame(127, $row['foo']);
        }

        $result = $driver
            ->select('some_table')
            ->condition('foo', 127)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
    }

    /**
     * Update RETURNING
     *
     * @dataProvider driverDataSource
     */
    public function testUpateReturning($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        if (!$driver->supportsReturning()) {
            $this->markTestSkipped("driver does not support RETURNING");
        }

        $result = $driver
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
     *
     * @dataProvider driverDataSource
     */
    public function testUpateSetExpressionColumn($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $result = $driver
            ->update('some_table', 't')
            ->set('foo', new ExpressionColumn('u.id'))
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $driver
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
     *
     * @dataProvider driverDataSource
     */
    public function testUpateSetExpressionRaw($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $result = $driver
            ->update('some_table', 't')
            ->set('foo', new ExpressionRaw('u.id'))
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $driver
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
     *
     * @dataProvider driverDataSource
     */
    public function testUpateSetSelectQuery($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $selectValueQuery = $driver
            ->select('users', 'z')
            ->columnExpression('z.id + 7')
            ->expression('z.id = id_user')
        ;

        $result = $driver
            ->update('some_table')
            ->set('foo', $selectValueQuery)
            ->condition('id_user', $this->idJean)
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());

        $result = $driver
            ->select('some_table')
            ->condition('id_user', $this->idJean)
            ->execute()
        ;
        foreach ($result as $row) {
            $this->assertSame($row['id_user'] + 7, $row['foo']);
        }

        $result = $driver
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
     *
     * @dataProvider driverDataSource
     */
    public function testUpateSetSqlStatement($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $result = $driver
            ->update('some_table')
            ->set('foo', new ExpressionRaw('id_user * 2'))
            ->join('users', 'u.id = id_user', 'u')
            ->condition('id_user', $this->idJean)
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());
    }
}
