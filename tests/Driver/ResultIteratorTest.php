<?php

declare(strict_types=1);

namespace Goat\Tests\Driver;

use Goat\Error\GoatError;
use Goat\Error\InvalidDataAccessError;
use Goat\Runner\PagerResultIterator;
use Goat\Runner\RunnerInterface;
use Goat\Tests\Driver\Mock\TestTypeEntity;
use Goat\Tests\DriverTestCase;

class ResultIteratorTest extends DriverTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(RunnerInterface $driver)
    {
        $driver->query("
            create temporary table type_test (
                foo integer,
                bar varchar(255),
                baz timestamp,
                some_ts timestamp default now(),
                some_time time default null,
                some_date date default null
            )
        ");
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(RunnerInterface $driver)
    {
        // ensure table data has the right types
        $driver->query("
            insert into type_test (foo, bar, baz, some_ts, some_time, some_date) values ($*::int4, $*::varchar, $*::timestamp, $*::timestamp, $*::time, $*::date);
        ", [
            42,
            'cassoulet',
            \DateTime::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00'),
            \DateTime::createFromFormat('Y-m-d H:i:s', '1993-03-22 09:25:00'),
            \DateTime::createFromFormat('Y-m-d H:i:s', '2003-03-22 10:25:00'),
            \DateTime::createFromFormat('Y-m-d H:i:s', '2013-03-22 11:25:00'),
        ]);
    }

    /**
     * Test basic result iterator usage
     *
     * @dataProvider driverDataSource
     */
    public function testBasicUsage($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $results = $driver->query("select * from type_test");
        $this->assertCount(1, $results);

        foreach ($results as $result) {
            $this->assertTrue(is_int($result['foo']));
            $this->assertTrue(is_string($result['bar']));
            $this->assertInstanceOf('\DateTimeImmutable', $result['baz']);
            $this->assertSame('1983-03-22 08:25:00', $result['baz']->format('Y-m-d H:i:s'));
            $this->assertInstanceOf('\DateTimeImmutable', $result['some_ts']);
            $this->assertSame('1993-03-22 09:25:00', $result['some_ts']->format('Y-m-d H:i:s'));
            $this->assertInstanceOf('\DateTimeImmutable', $result['some_time']);
            $this->assertSame('10:25:00', $result['some_time']->format('H:i:s'));
            $this->assertInstanceOf('\DateTimeImmutable', $result['some_date']);
            $this->assertSame('2013-03-22', $result['some_date']->format('Y-m-d'));
        }

        // and a prepared query for fun
        $identifier = $driver->prepareQuery('select foo, bar, baz, some_time from type_test');

        $results = $driver->executePreparedQuery($identifier);
        $this->assertSame(['foo', 'bar', 'baz', 'some_time'], $results->getColumnNames());
        $this->assertSame(4, $results->countColumns());
        $this->assertSame('foo', $results->getColumnName(0));
        $this->assertSame('int4', $results->getColumnType('foo'));
        $this->assertSame('bar', $results->getColumnName(1));
        $this->assertSame('varchar', $results->getColumnType('bar'));
        $this->assertSame('baz', $results->getColumnName(2));
        $this->assertSame('timestamp', $results->getColumnType('baz'));
        $this->assertSame('some_time', $results->getColumnName(3));
        $this->assertSame('time', $results->getColumnType('some_time'));
    }

    /**
     * Test passing options in the query
     *
     * @dataProvider driverDataSource
     */
    public function testOptions($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $select = $driver->select('type_test');
        $select->setOption('class', TestTypeEntity::class);
        $result = $select->range(1, 0)->execute();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(TestTypeEntity::class, $result->fetch());

        $select->setOption('class', null);
        $result = $select->execute();
        $this->assertCount(1, $result);
        $this->assertNotInstanceOf(TestTypeEntity::class, $result->fetch());

        $select->setOptions(['class' => TestTypeEntity::class]);
        $result = $select->execute();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(TestTypeEntity::class, $result->fetch());
    }

    /**
     * Test the empty iterator implementation
     *
     * @dataProvider driverDataSource
     */
    public function testPager($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        // Create lots of entries
        $insert = $driver->insertValues('type_test')->columns(['foo', 'bar']);
        for ($i = 0; $i < 99; ++$i) {
            $insert->values([$i, 'boo' . $i]);
        }
        $insert->execute();

        $limit = 13;
        $select = $driver
            ->select('type_test')
            ->range($limit, 2 * $limit)
        ;

        $count = $select->getCountQuery()->execute()->fetchField();
        $result = $select->execute();

        $pager = new PagerResultIterator($result, $count, $limit, 3);
        $this->assertSame($limit, $pager->count());
        $this->assertSame($result, $pager->getResult());
        $this->assertSame(2 * $limit, $pager->getStartOffset());
        $this->assertSame(3 * $limit, $pager->getStopOffset());
        $this->assertSame(8, $pager->getLastPage());
        $this->assertSame(3, $pager->getCurrentPage());
        $this->assertTrue($pager->hasNextPage());
        $this->assertTrue($pager->hasPreviousPage());
        $this->assertSame(100, $pager->getTotalCount());
        $this->assertSame($limit, $pager->getLimit());

        // @todo
        // countColumns() : int
        // countRows() : int
        // columnExists(string $name) : bool
        // getColumnNames() : array
        // getColumnType(string $name) : string
        // getColumnName(int $index) : string
        // fetchField($name = null)
        // fetchColumn($name = null)
        // fetch()

        //
        try {
            $pager = new PagerResultIterator($result, $count, $limit, 0);
            $this->fail();
        } catch (GoatError $e) {
        }

        // We are deliberatly lying to the pager, but don't care
        $pager = new PagerResultIterator($result, $count, $limit, 1);
        $this->assertSame(0, $pager->getStartOffset());
        $this->assertSame($limit, $pager->getStopOffset());
        $this->assertSame(8, $pager->getLastPage());
        $this->assertSame(1, $pager->getCurrentPage());
        $this->assertTrue($pager->hasNextPage());
        $this->assertFalse($pager->hasPreviousPage());

        // We are deliberatly lying to the pager, but don't care
        $pager = new PagerResultIterator($result, $count, $limit, 8);
        $this->assertSame(7 * $limit, $pager->getStartOffset());
        $this->assertSame(100, $pager->getStopOffset());
        $this->assertSame(8, $pager->getLastPage());
        $this->assertSame(8, $pager->getCurrentPage());
        $this->assertFalse($pager->hasNextPage());
        $this->assertTrue($pager->hasPreviousPage());
    }

    /**
     * Test the empty iterator implementation
     *
     * @dataProvider driverDataSource
     */
    public function testEmptyIterator($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        // Drivers, when going through a non returning query, must return
        // an empty iterator instance instead of a real iterator instance
        $empty = $driver
            ->update('type_test')
            ->condition('bar', 'cassoulet')
            ->set('foo', 137)
            ->execute()
        ;

        $this->assertSame(1, $empty->countRows());
        $this->assertSame(0, $empty->countColumns());
        $this->assertCount(0, $empty);
        $this->assertEmpty($empty);
        $this->assertFalse($empty->columnExists('anything'));
        $this->assertSame([], $empty->getColumnNames());
        $this->assertNull($empty->fetch());

        try {
            $empty->getColumnType("anything");
            $this->fail();
        } catch (InvalidDataAccessError $e) {
        }

        try {
            $empty->getColumnName(0);
            $this->fail();
        } catch (InvalidDataAccessError $e) {
        }

        try {
            $empty->fetchField("anything");
            $this->fail();
        } catch (InvalidDataAccessError $e) {
        }

        try {
            $empty->fetchColumn("anything");
            $this->fail();
        } catch (InvalidDataAccessError $e) {
        }

        $empty = $driver
            ->update('type_test')
            ->condition('bar', 'non existing column')
            ->set('foo', 137)
            ->execute()
        ;

        $this->assertSame(0, $empty->countRows());
    }
}
