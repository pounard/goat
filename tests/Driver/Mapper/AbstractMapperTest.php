<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Error\QueryError;
use Goat\Mapper\MapperInterface;
use Goat\Mapper\WritableMapperInterface;
use Goat\Mapper\Error\EntityNotFoundError;
use Goat\Query\ExpressionRaw;
use Goat\Query\Where;
use Goat\Runner\RunnerInterface;
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
    protected function createTestSchema(RunnerInterface $driver)
    {
        $driver->execute("
            create temporary table some_entity (
                id serial primary key,
                id_user integer default null,
                status integer default 1,
                foo integer not null,
                bar varchar(255),
                baz timestamp not null
            )
        ");
        $driver->execute("
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
     * Does this mapper supports join
     */
    abstract protected function supportsJoin() : bool;

    /**
     * Create the mapper to test
     *
     * @param RunnerInterface $driver
     *   Current connection to test with
     * @param string $class
     *   Object class to use for hydrators
     * @param string[] $primaryKey
     *   Entity primary key definition
     *
     * @return MapperInterface
     */
    abstract protected function createMapper(RunnerInterface $driver, string $class, array $primaryKey) : MapperInterface;

    /**
     * Create writable mapper to test
     *
     * @param RunnerInterface $driver
     *   Current connection to test with
     * @param string $class
     *   Object class to use for hydrators
     * @param string[] $primaryKey
     *   Entity primary key definition
     *
     * @return MapperInterface
     */
    abstract protected function createWritableMapper(RunnerInterface $driver, string $class, array $primaryKey) : WritableMapperInterface;

    /**
     * Tests various utility methods
     *
     * @dataProvider driverDataSource
     */
    public function testUtilityMethods($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $mapper = $this->createMapper($driver, MappedEntity::class, ['t.id']);
        $relation = $mapper->getRelation();
        $this->assertSame('some_entity', $relation->getName());
        $this->assertSame('t', $relation->getAlias());
        $this->assertSame(MappedEntity::class, $mapper->getClassName());
        $this->assertSame($driver, $mapper->getRunner());

        $mapper = $this->createWritableMapper($driver, MappedEntity::class, ['t.id']);
        $relation = $mapper->getRelation();
        $this->assertSame('some_entity', $relation->getName());
        $this->assertSame('t', $relation->getAlias());
        $this->assertSame(MappedEntity::class, $mapper->getClassName());
        $this->assertSame($driver, $mapper->getRunner());
    }

    /**
     * Tests find by primary key(s) feature
     *
     * @dataProvider driverDataSource
     */
    public function testFind($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);
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
        $driver = $this->createRunner($driverName, $class);
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
        $driver = $this->createRunner($driverName, $class);
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
        $supportsJoin = $this->supportsJoin();
        $driver = $this->createRunner($driverName, $class);
        $mapper = $this->createMapper($driver, MappedEntity::class, ['id']);

        // Most simple condition ever
        $result = $mapper->findBy(['id_user' => $this->idAdmin]);
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
        }

        // Using a single expression
        $result = $mapper->findBy(new ExpressionRaw('id_user = $*', [$this->idAdmin]));
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
        }

        // Using a Where instance
        $result = $mapper->findBy((new Where())->condition('id_user', $this->idAdmin));
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
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
            if ($supportsJoin) {
                $this->assertSame("jean", $item->name);
            }
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
        $supportsJoin = $this->supportsJoin();
        $driver = $this->createRunner($driverName, $class);
        $mapper = $this->createMapper($driver, MappedEntity::class, ['id']);

        // Most simple condition ever
        $result = $mapper->paginate(['id_user' => $this->idAdmin], 3, 2);
        $this->assertSame(2, $result->getCurrentPage());
        $this->assertSame(3, $result->getLastPage());
        $this->assertCount(3, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
        }

        // Using a single expression
        $result = $mapper->paginate(new ExpressionRaw('id_user = $*', [$this->idAdmin]));
        $this->assertCount(7, $result);
        foreach ($result as $item) {
            $this->assertTrue($item instanceof MappedEntity);
            $this->assertSame($this->idAdmin, $item->id_user);
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
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
            if ($supportsJoin) {
                $this->assertSame("admin", $item->name);
            }
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
            if ($supportsJoin) {
                $this->assertSame("jean", $item->name);
            }
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
     * @dataProvider driverDataSource
     */
    public function testCreate($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);
        $mapper = $this->createWritableMapper($driver, MappedEntity::class, ['id']);

        if (!$driver->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $entity1 = $mapper->create([
            'foo' => 113,
            'bar' => 'Created entity 1',
            'baz' => new \DateTime(),
        ]);
        $this->assertTrue($entity1 instanceof MappedEntity);
        $this->assertSame(113, $entity1->foo);
        $this->assertSame("Created entity 1", $entity1->bar);

        $entity2 = $mapper->create([
            'foo' => 1096,
            'bar' => 'Created entity 2',
            'baz' => new \DateTime(),
        ]);
        $this->assertTrue($entity2 instanceof MappedEntity);
        $this->assertSame(1096, $entity2->foo);
        $this->assertSame("Created entity 2", $entity2->bar);

        $result = $mapper->findAll([$entity1->id, $entity2->id]);
        $this->assertSame(2, $result->countRows());
    }

    /**
     * @dataProvider driverDataSource
     */
    public function testCreateFrom($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);
        $mapper = $this->createWritableMapper($driver, MappedEntity::class, ['id']);

        if (!$driver->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $entity = $driver->getHydratorMap()->get(MappedEntity::class)->createAndHydrateInstance([
            'foo' => 113,
            'bar' => 'Created entity 1',
            'baz' => new \DateTime(),
        ]);
        $this->assertTrue($entity instanceof MappedEntity);
        $this->assertSame(113, $entity->foo);
        $this->assertSame("Created entity 1", $entity->bar);
        $this->assertEmpty($entity->id);

        $created = $mapper->createFrom($entity);
        $this->assertNotSame($entity, $created);
        $this->assertTrue($created instanceof MappedEntity);
        $this->assertSame(113, $created->foo);
        $this->assertSame("Created entity 1", $created->bar);
        $this->assertNotEmpty($created->id);
    }

    /**
     * @dataProvider driverDataSource
     */
    public function testUpdate($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);
        $mapper = $this->createWritableMapper($driver, MappedEntity::class, ['id']);

        if (!$driver->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $updated = $mapper->update(9, [
            'bar' => 'The new bar value',
            'status' => 112,
        ]);
        $this->assertTrue($updated instanceof MappedEntity);
        $this->assertSame(9, $updated->id);
        $this->assertSame('The new bar value', $updated->bar);
        $this->assertSame(112, $updated->status);

        $reloaded = $mapper->findOne(9);
        $this->assertTrue($reloaded instanceof MappedEntity);
        $this->assertSame('The new bar value', $reloaded->bar);
        $this->assertSame(112, $reloaded->status);
    }

    /**
     * @dataProvider driverDataSource
     */
    public function testUpdateFrom($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);
        $mapper = $this->createWritableMapper($driver, MappedEntity::class, ['id']);

        if (!$driver->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $reference = $mapper->findOne(5);
        $this->assertTrue($reference instanceof MappedEntity);
        $this->assertSame(5, $reference->id);
        $this->assertSame('foo', $reference->bar);

        $toBeUpdated = $mapper->findOne(9);
        $this->assertSame(9, $toBeUpdated->id);
        $this->assertSame('baz', $toBeUpdated->bar);

        $updated = $mapper->updateFrom(9, $reference);
        $this->assertInstanceOf(MappedEntity::class, $updated);
        $this->assertSame(9, $updated->id);
        $this->assertSame('foo', $updated->bar);

        $updatedReloaded = $mapper->findOne(9);
        $this->assertInstanceOf(MappedEntity::class, $updatedReloaded);
        $this->assertSame(9, $updatedReloaded->id);
        $this->assertSame('foo', $updatedReloaded->bar);

        // Assert that original has not changed (no side effect)
        $this->assertInstanceOf(MappedEntity::class, $reference);
        $this->assertSame(5, $reference->id);
        $this->assertSame('foo', $reference->bar);

        // and the same by reloading it
        $reloaded = $mapper->findOne(5);
        $this->assertInstanceOf(MappedEntity::class, $reloaded);
        $this->assertSame(5, $reloaded->id);
        $this->assertSame('foo', $reloaded->bar);

        try {
            $mapper->delete(666, true);
            $this->fail("updating from to a non existing row should have raised an exception");
        } catch (\Exception $e) {
            $this->assertTrue(true, "updating from to a non existing row raised an exception");
        }
    }

    /**
     * @dataProvider driverDataSource
     */
    public function testDelete($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);
        $mapper = $this->createWritableMapper($driver, MappedEntity::class, ['id']);

        if (!$driver->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $deleted = $mapper->delete(11, false);
        $this->assertInstanceOf(MappedEntity::class, $deleted);
        $this->assertSame(11, $deleted->id);

        $deleted = $mapper->delete(11, false);
        $this->assertEmpty($deleted);

        try {
            $mapper->delete(666, true);
            $this->fail("deleting a non existing row should have raised an exception");
        } catch (\Exception $e) {
            $this->assertTrue(true, "deleting a non existing row raised an exception");
        }
    }
}

