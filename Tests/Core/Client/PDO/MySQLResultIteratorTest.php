<?php

namespace Goat\Tests\Core\Client;

use Goat\Tests\ConnectionAwareTestTrait;

class MySQLResultIteratorTest extends \PHPUnit_Framework_TestCase
{
    use ConnectionAwareTestTrait;

    protected function getDriver()
    {
        return 'MYSQL';
    }

    public function testConnection()
    {
        $connection = $this->getConnection();

        $connection->query("
            create temporary table type_test (
                foo integer unsigned,
                bar varchar(255),
                baz datetime,
                some_ts datetime,
                some_time time default null,
                some_date date default null
            )
        ");

        // ensure table data has the right types
        $connection->query("
            insert into type_test (foo, bar, baz, some_ts, some_time, some_date) values ($*::int4, $*::varchar, $*::timestamp, $*::timestamp, $*::time, $*::date);
        ", [
            42,
            'cassoulet',
            \DateTime::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00'),
            \DateTime::createFromFormat('Y-m-d H:i:s', '1993-03-22 09:25:00'),
            \DateTime::createFromFormat('Y-m-d H:i:s', '2003-03-22 10:25:00'),
            \DateTime::createFromFormat('Y-m-d H:i:s', '2013-03-22 11:25:00'),
        ]);

        $results = $connection->query("select * from type_test");
        $this->assertCount(1, $results);

        foreach ($results as $result) {
            $this->assertTrue(is_int($result['foo']));
            $this->assertTrue(is_string($result['bar']));
            $this->assertInstanceOf('\DateTime', $result['baz']);
            $this->assertSame('1983-03-22 08:25:00', $result['baz']->format('Y-m-d H:i:s'));
            $this->assertInstanceOf('\DateTime', $result['some_ts']);
            $this->assertSame('1993-03-22 09:25:00', $result['some_ts']->format('Y-m-d H:i:s'));
            $this->assertInstanceOf('\DateTime', $result['some_time']);
            $this->assertSame('10:25:00', $result['some_time']->format('H:i:s'));
            $this->assertInstanceOf('\DateTime', $result['some_date']);
            $this->assertSame('2013-03-22', $result['some_date']->format('Y-m-d'));
        }

        // and a prepared query for fun
        $identifier = $connection->prepareQuery('select foo, bar, baz, some_time from type_test');

        $results = $connection->executePreparedQuery($identifier);
        $this->assertSame(4, $results->countFields());
        $this->assertSame('foo', $results->getFieldName(0));
        $this->assertSame('int4', $results->getFieldType('foo'));
        $this->assertSame('bar', $results->getFieldName(1));
        $this->assertSame('varchar', $results->getFieldType('bar'));
        $this->assertSame('baz', $results->getFieldName(2));
        $this->assertSame('timestamp', $results->getFieldType('baz'));
        $this->assertSame('some_time', $results->getFieldName(3));
        $this->assertSame('time', $results->getFieldType('some_time'));
    }
}
