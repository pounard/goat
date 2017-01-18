<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Query\Query;
use Goat\Mapper\MapperInterface;
use Goat\Tests\DriverTestCase;

/**
 * Basics unit/functional testing for all mappers
 */
abstract class AbstractMapperTest extends DriverTestCase
{
    private $idAdmin;
    private $idJean;

    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(ConnectionInterface $connection)
    {
        $connection->query("
            create temporary table some_entity (
                id serial primary key,
                id_user integer default null,
                status integer default 1,
                foo integer not null,
                bar varchar(255),
                baz timestamp not null
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
            ->insertValues('some_entity')
            ->columns(['foo', 'status', 'bar', 'id_user', 'baz'])
            ->values([2,  1, 'foo', $this->idAdmin, new \DateTime()])
            ->values([3,  1, 'bar', $this->idJean, new \DateTime('now +1 day')])
            ->values([5,  1, 'baz', $this->idAdmin, new \DateTime('now -2 days')])
            ->values([7,  1, 'foo', $this->idAdmin, new \DateTime('now -6 hours')])
            ->values([11, 1, 'foo', $this->idJean, new \DateTime()])
            ->values([13, 0, 'bar', $this->idJean, new \DateTime('now -3 months')])
            ->values([17, 0, 'bar', $this->idAdmin, new \DateTime('now -3 years')])
            ->values([19, 0, 'baz', $this->idAdmin, new \DateTime()])
            ->values([23, 0, 'baz', $this->idJean, new \DateTime('now +7 years')])
            ->values([29, 1, 'foo', $this->idJean, new \DateTime('now +2 months')])
            ->values([31, 0, 'foo', $this->idJean, new \DateTime('now +17 hours')])
            ->values([37, 2, 'foo', $this->idAdmin, new \DateTime('now -128 hours')])
            ->values([41, 2, 'bar', $this->idAdmin, new \DateTime('now -8 days')])
            ->values([43, 2, 'bar', $this->idAdmin, new \DateTime('now -6 minutes')])
            ->execute()
        ;
    }

    /**
     * Create the mapper to test
     *
     * @param ConnectionInterface $connection
     *   Current connection to test with
     * @param string $class
     *   Object class to use for hydrators
     * @param string[] $primaryKey
     *   Entity primary key definition
     *
     * @return MapperInterface
     */
    abstract protected function createMapper(ConnectionInterface $connection, string $class, array $primaryKey) : MapperInterface;

    /**
     * Tests find by primary key(s) feature
     *
     * @dataProvider driverDataSource
     */
    public function testFind($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

        $mapper = $this->createMapper($connection, $class, ['id']);
    }

    /**
     * Tests find by primary key(s) feature when primary key has more than one column
     *
     * @dataProvider driverDataSource
     */
    public function testFindMultiple($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

        $mapper = $this->createMapper($connection, $class, ['id', 'foo']);
    }

    /**
     * Tests find by criteria
     *
     * @dataProvider driverDataSource
     */
    public function testFindByCriteria($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

        $mapper = $this->createMapper($connection, $class, ['id']);
    }

    /**
     * Tests find by criteria when primary key has more than one column
     *
     * @dataProvider driverDataSource
     */
    public function testFindByCriteriaMultiple($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

        $mapper = $this->createMapper($connection, $class, ['id', 'foo']);
    }

    /**
     * Tests pagination
     *
     * @dataProvider driverDataSource
     */
    public function testPaginate($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

        $mapper = $this->createMapper($connection, $class, ['id']);
    }

    /**
     * Tests pagination when primary key has more than one column
     *
     * @dataProvider driverDataSource
     */
    public function testPaginateMultiple($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

        $mapper = $this->createMapper($connection, $class, ['id', 'foo']);
    }
}
