<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Error\GoatError;
use Goat\Core\Query\Query;
use Goat\Tests\ConnectionAwareTest;

abstract class AbstractNamedParametersTest extends ConnectionAwareTest
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
     * Test select query with named parameters
     */
    public function testNamedParameterSelect()
    {
        $connection = $this->getConnection();

        $query = $connection->select('some_table');
        $query
            ->where()
            ->or()
            ->condition('foo', ':some_foo::integer')
            ->condition('bar', ':barbar')
        ;

        // Both conditions matches the same line, result should be 1
        $result = $query->execute(Query::RET_PROXY, [
            'some_foo' => 42,
            'barbar' => 'a',
        ]);
        $this->assertCount(1, $result);

        // Both conditions matches different lines, result should be 2
        $result = $query->execute(Query::RET_PROXY, [
            'some_foo' => 666,
            'barbar' => 'a',
        ]);
        $this->assertCount(2, $result);

        // Reverse order, and it should still work
        $result = $query->execute(Query::RET_PROXY, [
            'barbar' => 'b',
            'some_foo' => 37,
        ]);
        $this->assertCount(2, $result);

        // Those parameters don't exist, this should fail
        try {
            $result = $query->execute(Query::RET_PROXY, [
                'fouque' => 'b',
                'yoo' => 37,
            ]);
            $this->fail();
        } catch (GoatError $e) {
            // Success
        }
    }

    /**
     * Test a raw SQL query with named parameters
     */
    public function testNamedParameterRawQuery()
    {
        $this->markTestIncomplete("not implemented yet");
    }

    /**
     * Test insert query with named parameters
     */
    public function testNamedParameterInsert()
    {
        $this->markTestIncomplete("not implemented yet");
    }
}
