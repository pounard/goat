<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Core\Error\QueryError;
use Goat\Driver\DriverInterface;
use Goat\Mapper\Error\EntityNotFoundError;
use Goat\Mapper\MapperInterface;
use Goat\Query\ExpressionRaw;
use Goat\Query\Query;
use Goat\Query\Where;
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
    protected function createTestSchema(DriverInterface $driver)
    {
        $driver->query("
            create temporary table some_entity (
                id serial primary key,
                id_user integer default null,
                status integer default 1,
                foo integer not null,
                bar varchar(255),
                baz timestamp not null
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
            ->values([41, 2, 'bar', $this->idJean, new \DateTime('now -8 days')])
            ->values([43, 2, 'bar', $this->idAdmin, new \DateTime('now -6 minutes')])
            ->execute()
        ;
    }

    /**
     * Create the mapper to test
     *
     * @param DriverInterface $driver
     *   Current connection to test with
     * @param string $class
     *   Object class to use for hydrators
     * @param string[] $primaryKey
     *   Entity primary key definition
     *
     * @return MapperInterface
     */
    abstract protected function createMapper(DriverInterface $driver, string $class, array $primaryKey) : MapperInterface;

    /**
     * Create writable mapper to test
     *
     * @param DriverInterface $driver
     *   Current connection to test with
     * @param string $class
     *   Object class to use for hydrators
     * @param string[] $primaryKey
     *   Entity primary key definition
     *
     * @return MapperInterface
     */
    abstract protected function createWritableMapper(DriverInterface $driver, string $class, array $primaryKey) : MapperInterface;

    /**
     * Tests various utility methods
     *
     * @dataProvider driverDataSource
     */
    public function testUtilityMethods($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);

        $mapper = $this->createMapper($driver, MappedEntity::class, ['t.id']);
        $relation = $mapper->getRelation();
        $this->assertSame('some_entity', $relation->getName());
        $this->assertSame('t', $relation->getAlias());
        $this->assertSame(MappedEntity::class, $mapper->getClassName());
        $this->assertSame($driver, $mapper->getDriver());

        $mapper = $this->createWritableMapper($driver, MappedEntity::class, ['t.id']);
        $relation = $mapper->getRelation();
        $this->assertSame('some_entity', $relation->getName());
        $this->assertSame('t', $relation->getAlias());
        $this->assertSame(MappedEntity::class, $mapper->getClassName());
        $this->assertSame($driver, $mapper->getDriver());
    }

    /**
     * Tests find by primary key(s) feature
     *
     * @dataProvider driverDataSource
     */
    public function testFind($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);
        $mapper = $this->createMapper($driver, MappedEntity::class, ['t.id']);

        foreach ([1, [1]] as $id) {
            $item1 = $mapper->findOne($id);
            $this->assertTrue($item1 instanceof MappedEntity);
            // This also tests there is no conflict between table columns
            $this->assertSame(1, $item1->id);
        }

        foreach ([8, [8]] as $id) {
            $item8 = $mapper->findOne($id);
            $this->assertTrue($item8 instanceof MappedEntity);
            // This also tests there is no conflict between table columns
            $this->assertSame(8, $item8->id);
        }

        $this->assertNotSame($item1, $item8);

        // Also ensure that the user can legally be stupid
        try {
            $mapper->findOne([1, 12]);
            $this->fail();
        } catch (QueryError $e) {
        }

        foreach ([[2, 3], [[2], [3]]] as $idList) {
            $result = $mapper->findAll($idList);
            $this->assertCount(2, $result);
            $item2or3 = $result->fetch();
            $this->assertTrue($item2or3 instanceof MappedEntity);
            // This also tests there is no conflict between table columns
            $this->assertContains($item2or3->id, [2, 3]);
        }
    }

    /**
     * Tests find by primary key(s) feature
     *
     * @dataProvider driverDataSource
     */
    public function testFindFirst($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);
        $mapper = $this->createMapper($driver, MappedEntity::class, ['t.id']);

        $item1 = $mapper->findFirst(['id_user' => $this->idAdmin]);
        $this->assertInstanceOf(MappedEntity::class, $item1);
        $this->assertSame($item1->id_user, $this->idAdmin);

        $item2 = $mapper->findFirst(['id_user' => -1], false);
        $this->assertNull($item2);

        $item3 = $mapper->findFirst(['id_user' => -1]);
        $this->assertNull($item3);

        // Also ensure that the user can legally be stupid
        try {
            $mapper->findFirst(['id_user' => -1], true);
            $this->fail();
        } catch (EntityNotFoundError $e) {
        }
    }

    /**
     * Tests find by primary key(s) feature when primary key has more than one column
     *
     * @dataProvider driverDataSource
     */
    public function testFindMultiple($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);
        $mapper = $this->createMapper($driver, MappedEntity::class, ['foo', 'status']);

        $item1 = $mapper->findOne([2, 1]);
        $this->assertTrue($item1 instanceof MappedEntity);
        // This also tests there is no conflict between table columns
        $this->assertSame(2, $item1->foo);
        $this->assertSame(1, $item1->status);

        $result = $mapper->findAll([[2, 1], [23, 0], [999, 1000]]);
        $this->assertCount(2, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertContains($item->foo, [2, 23]);
            $this->assertContains($item->status, [1, 0]);
        }
    }

    /**
     * Tests find by criteria
     *
     * @dataProvider driverDataSource
     */
    public function testFindByCriteria($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);
        $mapper = $this->createMapper($driver, MappedEntity::class, ['id']);

        // Most simple condition ever
        $result = $mapper->findBy(['id_user' => $this->idAdmin]);
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            $this->assertSame("admin", $item->name);
        }

        // Using a single expression
        $result = $mapper->findBy(new ExpressionRaw('id_user = $*', [$this->idAdmin]));
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            $this->assertSame("admin", $item->name);
        }

        // Using a Where instance
        $result = $mapper->findBy((new Where())->condition('id_user', $this->idAdmin));
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            $this->assertSame("admin", $item->name);
        }

        // More than one condition
        $result = $mapper
            ->findBy([
                'id_user' => $this->idJean,
                new ExpressionRaw('baz < $*', [new \DateTime("now -1 second")])
            ])
        ;
        $this->assertCount(2, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idJean, $item->id_user);
            $this->assertSame("jean", $item->name);
            $this->assertLessThan(new \DateTime("now -1 second"), $item->baz);
        }

        // Assert that user can be stupid sometime
        try {
            $mapper->findBy('oh you you you');
            $this->fail();
        } catch (QueryError $e) {
        }
    }

    /**
     * Tests pagination
     *
     * @dataProvider driverDataSource
     */
    public function testPaginate($driverName, $class)
    {
        $driver = $this->createDriver($driverName, $class);
        $mapper = $this->createMapper($driver, MappedEntity::class, ['id']);

        // Most simple condition ever
        $result = $mapper->paginate(['id_user' => $this->idAdmin], 3, 2);
        $this->assertSame(2, $result->getCurrentPage());
        $this->assertSame(3, $result->getLastPage());
        $this->assertCount(3, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            $this->assertSame("admin", $item->name);
        }

        // Using a single expression
        $result = $mapper->paginate(new ExpressionRaw('id_user = $*', [$this->idAdmin]));
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            $this->assertSame("admin", $item->name);
        }

        // Using a Where instance
        $result = $mapper->paginate((new Where())->condition('id_user', $this->idAdmin), 6, 1);
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(2, $result->getLastPage());
        $this->assertTrue($result->hasNextPage());
        $this->assertFalse($result->hasPreviousPage());
        $this->assertCount(6, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            $this->assertSame("admin", $item->name);
        }

        // More than one condition
        $result = $mapper
            ->paginate([
                'id_user' => $this->idJean,
                new ExpressionRaw('baz < $*', [new \DateTime("now -1 second")])
            ], 10, 1)
        ;
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(1, $result->getLastPage());
        $this->assertFalse($result->hasNextPage());
        $this->assertFalse($result->hasPreviousPage());
            $this->assertCount(2, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idJean, $item->id_user);
            $this->assertSame("jean", $item->name);
            $this->assertLessThan(new \DateTime("now -1 second"), $item->baz);
        }

        // Assert that user can be stupid sometime
        try {
            $mapper->findBy('oh you you you');
            $this->fail();
        } catch (QueryError $e) {
        }
    }
}

