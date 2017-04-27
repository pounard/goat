<?php

declare(strict_types=1);

namespace Goat\Tests\Driver;

use Goat\Driver\DriverInterface;
use Goat\Query\Query;
use Goat\Tests\Driver\Mock\DeleteSomeTableWithUser;
use Goat\Tests\DriverTestCase;

class DeleteTest extends DriverTestCase
{
    private $idAdmin;
    private $idJean;

    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(DriverInterface $driver)
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
    protected function createTestData(DriverInterface $driver)
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
            ->insertValues('users_status')
            ->columns(['id_user', 'status'])
            ->values([$this->idAdmin, 7])
            ->values([$this->idJean, 11])
            ->values([$this->idJean, 17])
            ->execute()
        ;

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
     * Test simple DELETE FROM WHERE
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteWhere($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $driver
            ->delete('some_table', 't')
            ->condition('t.id_user', $this->idJean)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        $result = $driver
            ->delete('some_table')
            ->condition('bar', 'a')
            ->execute()
        ;
        $this->assertSame(1, $result->countRows());
        $this->assertSame(2, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());
        $this->assertSame(0, $driver->query("select count(*) from some_table where bar = $*::varchar", ['a'])->fetchField());

        // For fun, test with a named parameter
        $result = $driver
            ->delete('some_table')
            ->condition('bar', ':bar::varchar')
            ->execute([
                'bar' => 'e',
            ])
        ;
        $this->assertSame(1, $result->countRows());
        $this->assertSame(1, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(1, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());
        $this->assertSame(0, $driver->query("select count(*) from some_table where bar = $*::varchar", ['e'])->fetchField());
    }

    /**
     * Test simple DELETE FROM
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteAll($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $driver->delete('some_table')->execute();
        $this->assertSame(5, $result->countRows());
        $this->assertSame(0, $driver->query("select count(*) from some_table")->fetchField());
    }

    /**
     * Test DELETE FROM WHERE IN (SELECT ... )
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteWhereIn($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $whereInSelect = $driver
            ->select('users')
            ->column('id')
            ->condition('name', 'jean')
        ;

        $result = $driver
            ->delete('some_table')
            ->condition('id_user', $whereInSelect)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());
    }

    /**
     * Test DELETE FROM USING WHERE
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteUsing($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $driver
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', $this->idJean)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());
    }

    /**
     * Test simple DELETE FROM USING RETURNING
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteUsingReturning($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        if (!$driver->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $driver
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', $this->idJean)
            ->returning('t.id')
            ->returning('t.id_user')
            ->returning('u.name')
            ->returning('t.bar')
            ->execute()
        ;
        $this->assertCount(2, $result);
        $this->assertSame(3, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        foreach ($result as $row) {
            $this->assertSame($this->idJean, $row['id_user']);
            $this->assertSame('jean', $row['name']);
            $this->assertInternalType('integer', $row['id']);
            $this->assertInternalType('string', $row['bar']);
        }
    }

    /**
     * Test simple DELETE FROM USING RETURNING
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteUsingReturningAndHydrating($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);
        if (!$driver->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $driver
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', $this->idJean)
            ->returning('t.id')
            ->returning('t.id_user', 'userId')
            ->returning('u.name')
            ->returning('t.bar')
            ->execute([], DeleteSomeTableWithUser::class)
        ;
        $this->assertCount(2, $result);
        $this->assertSame(3, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        foreach ($result as $row) {
            $this->assertTrue($row instanceof DeleteSomeTableWithUser);
            $this->assertSame($this->idJean, $row->getUserId());
            $this->assertSame('jean', $row->getUserName());
            $this->assertInternalType('integer', $row->getId());
            $this->assertInternalType('string', $row->getBar());
        }
    }

    /**
     * Test DELETE FROM USING JOIN WHERE
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteUsingJoin($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $driver
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->join('users_status', 'u.id = st.id_user', 'st')
            ->condition('st.status', 5) // Does nothing
            ->execute()
        ;

        $this->assertSame(0, $result->countRows());
        $this->assertSame(5, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        $result = $driver
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->join('users_status', 'u.id = st.id_user', 'st')
            ->condition('st.status', 11) // Removes jean
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $driver->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());
        $this->assertSame(3, $driver->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());
    }
}
