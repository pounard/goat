<?php

namespace Goat\Tests\Core\Query;

use Goat\Tests\ConnectionAwareTestTrait;
use Goat\Core\Query\Query;

class PgSQLInsertValuesTest extends \PHPUnit_Framework_TestCase
{
    use ConnectionAwareTestTrait;

    /**
     * Get driver name
     *
     * @return string
     */
    protected function getDriver()
    {
        return 'PGSQL';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $connection = $this->getConnection();
        $connection->query("
            create temporary table some_table (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp default now()
            )
        ");
        $connection->query("
            create temporary table users (
                id serial primary key,
                name varchar
            )
        ");
        $connection->insertValues('users')->columns(['name'])->values(["admin"])->values(["jean"])->execute();
    }

    /**
     * Very simple test
     */
    public function testSingleValueInsert()
    {
        $connection = $this->getConnection();
        $referenceDate = new \DateTime();

        $connection
            ->insertValues('some_table')
            ->columns(['foo', 'bar', 'baz'])
            // @todo argument conversion on querybuilder!
            ->values([42, 'the big question', $referenceDate->format('Y-m-d H:i:s')])
            ->execute()
        ;

        $value = $connection
            ->select('some_table', 't')
            ->column('t.foo')
            ->column('t.bar')
            ->column('t.baz', 'date')
            ->orderBy('t.id', Query::ORDER_DESC)
            ->range(1)
            ->execute()
            ->fetch()
        ;

        $this->assertEquals($referenceDate, $value['date']);
    }

    /**
     * Okay, let's bulk!
     */
    public function testBulkValueInsert()
    {
        $connection = $this->getConnection();

        $insert = $connection
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->values([1, 'one'])
            // Attempt an SQL injection, this is a simple one
            ->values([666, "); delete from users; select ("])
        ;

        for ($i = 0; $i < 10; ++$i) {
            $ref = rand(0, 255);
            $insert->values([$ref, dechex($ref)]);
        }

        $insert->execute();

        /** @var \Goat\Core\Client\ResultIteratorInterface $result */
        $result = $connection
            ->select('some_table', 't')
            ->orderBy('t.id', Query::ORDER_ASC)
            ->execute()
        ;

        $this->assertSame(12, $result->count());

        $row1 = $result->fetch();
        $this->assertSame(1, $row1['foo']);

        $row2 = $result->fetch();
        $this->assertSame(666, $row2['foo']);
        $this->assertSame("); delete from users; select (", $row2['bar']);
    }

    /**
     * Test value insert with a RETURNING clause
     */
    public function testBulkValueInsertWithReturning()
    {
        $connection = $this->getConnection();

        // Add one value, so there is data in the table, it will ensure that
        // the returning count is the right one
        $result = $connection
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->values([1, 'a'])
            ->values([2, 'b'])
            ->execute();
        ;

        // Queries that don't return anything, in our case, an INSERT query
        // without the RETURNING clause, should not return anything
        $this->assertSame(0, $result->count());

        // But we should have an affected row count
        $this->assertSame(2, $result->countRows());

        // Add one value, so there is data in the table, it will ensure that
        // the returning count is the right one
        $affectedRowCount = $connection
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->values([3, 'c'])
            ->values([4, 'd'])
            ->values([5, '8'])
            ->perform();
        ;

        $this->assertSame(3, $affectedRowCount);

        $result = $connection
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->returning('id')
            ->returning('bar', 'miaw')
            ->values([12, 'boo'])
            ->values([13, 'baa'])
            ->values([14, 'bee'])
            ->execute();
        ;

        $this->assertSame(3, $result->countRows());

        // 'id' field is a sequence, and should start with 1
        $row1 = $result->fetch();
        $this->assertSame(6, $row1['id']);
        $this->assertNotContains('baz', $row1);
        $this->assertNotContains('bar', $row1);
        $this->assertSame('boo', $row1['miaw']);

        $row2 = $result->fetch();
        $this->assertSame(7, $row2['id']);
        $this->assertNotContains('baz', $row2);
        $this->assertNotContains('bar', $row2);
        $this->assertSame('baa', $row2['miaw']);

        $row3 = $result->fetch();
        $this->assertSame(8, $row3['id']);
        $this->assertNotContains('baz', $row3);
        $this->assertNotContains('bar', $row3);
        $this->assertSame('bee', $row3['miaw']);
    }

    /**
     * Tests that exceptions are raised when necessery
     */
    public function testQueryBuilderLogicErrors()
    {

    }
}
