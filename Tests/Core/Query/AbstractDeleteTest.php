<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Query\Query;
use Goat\Tests\ConnectionAwareTest;
use Goat\Tests\Core\Query\Mock\DeleteSomeTableWithUser;

abstract class AbstractDeleteTest extends ConnectionAwareTest
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
     * Test simple DELETE FROM WHERE
     */
    public function testDeleteWhere()
    {
        $connection = $this->getConnection();
        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $connection
            ->delete('some_table', 't')
            ->condition('t.id_user', $this->idJean)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        $result = $connection
            ->delete('some_table')
            ->condition('bar', 'a')
            ->execute()
        ;
        $this->assertSame(1, $result->countRows());
        $this->assertSame(2, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from some_table where bar = $*::varchar", ['a'])->fetchField());

        // For fun, test with a named parameter
        $result = $connection
            ->delete('some_table')
            ->condition('bar', ':bar::varchar')
            ->execute([
                'bar' => 'e',
            ])
        ;
        $this->assertSame(1, $result->countRows());
        $this->assertSame(1, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(1, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from some_table where bar = $*::varchar", ['e'])->fetchField());
    }

    /**
     * Test simple DELETE FROM
     */
    public function testDeleteAll()
    {
        $connection = $this->getConnection();
        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $connection->delete('some_table')->execute();
        $this->assertSame(5, $result->countRows());
        $this->assertSame(0, $connection->query("select count(*) from some_table")->fetchField());
    }

    /**
     * Test DELETE FROM WHERE IN (SELECT ... )
     */
    public function testDeleteWhereIn()
    {
        $connection = $this->getConnection();
        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $whereInSelect = $connection
            ->select('users')
            ->column('id')
            ->condition('name', 'jean')
        ;

        $result = $connection
            ->delete('some_table')
            ->condition('id_user', $whereInSelect)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());
    }

    /**
     * Test DELETE FROM USING WHERE
     */
    public function testDeleteUsing()
    {
        $connection = $this->getConnection();
        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $connection
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', $this->idJean)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());
    }

    /**
     * Test simple DELETE FROM USING RETURNING
     */
    public function testDeleteUsingReturning()
    {
        $connection = $this->getConnection();

        if (!$connection->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $connection = $this->getConnection();
        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $connection
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
        $this->assertSame(3, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        foreach ($result as $row) {
            $this->assertSame($this->idJean, $row['id_user']);
            $this->assertSame('jean', $row['name']);
            $this->assertInternalType('integer', $row['id']);
            $this->assertInternalType('string', $row['bar']);
        }
    }

    /**
     * Test simple DELETE FROM USING RETURNING
     */
    public function testDeleteUsingReturningAndHydrating()
    {
        $connection = $this->getConnection();

        if (!$connection->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $connection = $this->getConnection();
        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $connection
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
        $this->assertSame(3, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

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
     */
    public function testDeleteUsingJoin()
    {
        $connection = $this->getConnection();
        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $connection
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->join('users_status', 'u.id = st.id_user', 'st')
            ->condition('st.status', 5) // Does nothing
            ->execute()
        ;

        $this->assertSame(0, $result->countRows());
        $this->assertSame(5, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        $result = $connection
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->join('users_status', 'u.id = st.id_user', 'st')
            ->condition('st.status', 11) // Removes jean
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $connection->query("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());
        $this->assertSame(3, $connection->query("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());
    }
}