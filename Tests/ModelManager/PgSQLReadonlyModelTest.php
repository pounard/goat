<?php

namespace Goat\Tests\ModelManager;

use Goat\Core\Query\Where;
use Goat\ModelManager\DefaultEntity;
use Goat\ModelManager\EntityInterface;
use Goat\ModelManager\EntityStructure;
use Goat\ModelManager\ReadonlyModel;
use Goat\Tests\ConnectionAwareTestTrait;
use Goat\Tests\ModelManager\Mock\SomeStructure;

class PgSQLReadonlyModelTest extends \PHPUnit_Framework_TestCase
{
    use ConnectionAwareTestTrait;

    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }

    public function testReadOperations()
    {
        $connection = $this->getConnection();

        $connection->query("
            create temporary table some_entity (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp not null
            )
        ");

        $model = new ReadonlyModel($connection, new SomeStructure());

        $this->assertSame(0, $model->countWhere((new Where())->statement('1 = 1')));

        // Ok now insert stuff using raw SQL, this only tests the readonly
        // implementation not the full-on
        $connection->query("
            insert into some_entity (
                foo, bar, baz
            )
            values (
                -12, 'what', '2012-05-22 08:30:00'
            ), (
                24, 'cassoulet', '2012-05-22 08:30:00'
            ), (
                -48, 'salut les amis', '2012-05-22 08:30:00'
            ), (
                96, 'this one will probably test like', '2012-05-22 08:30:00'
            ), (
                -192, null, '2012-05-22 08:30:00'
            )
        ");

        $entities = $model->findAll();
        $this->assertCount(5, $entities);

        $entity = $model->findByPK(1);
        $this->assertTrue($entity instanceof EntityInterface);
        $this->assertSame(1, $entity->get('id'));
    }

    public function testPager()
    {
        $connection = $this->getConnection();

        $connection->query("
            create temporary table pagertest (
                a integer primary key
            )
        ");
        for ($i = 0; $i < 100; ++$i) {
            $connection->query("insert into pagertest (a) values (?)", [$i]);
        }

        $model = new ReadonlyModel(
            $connection,
            (new EntityStructure())
                ->setPrimaryKey(['a'])
                ->setEntityClass(DefaultEntity::class)
                ->setRelation('pagertest')
                ->addField('a', 'int4')
        );

        $this->assertSame(100, $model->countWhere());

        // Start
        $pager = $model->findAllWithPager(null, '', 7, 1);
        $this->assertSame(7, count($pager));
        $this->assertSame(100, $pager->getTotalCount());
        $this->assertSame(0, $pager->getStartOffset());
        $this->assertSame(7, $pager->getStopOffset());
        $this->assertSame(1, $pager->getCurrentPage());
        $this->assertSame(15, $pager->getLastPage());
        $this->assertFalse($pager->hasPreviousPage());
        $this->assertTrue($pager->hasNextPage());

        // Middle
        $pager = $model->findAllWithPager(null, '', 7, 3);
        $this->assertCount(7, $pager);
        $this->assertSame(14, $pager->getStartOffset());
        $this->assertSame(21, $pager->getStopOffset());
        $this->assertSame(3, $pager->getCurrentPage());
        $this->assertSame(15, $pager->getLastPage());
        $this->assertTrue($pager->hasPreviousPage());
        $this->assertTrue($pager->hasNextPage());

        // Important one: the last is not a full page
        $pager = $model->findAllWithPager(null, '', 7, 15);
        $this->assertCount(2, $pager);
        $this->assertSame(98, $pager->getStartOffset());
        $this->assertSame(100, $pager->getStopOffset());
        $this->assertSame(15, $pager->getCurrentPage());
        $this->assertSame(15, $pager->getLastPage());
        $this->assertTrue($pager->hasPreviousPage());
        $this->assertFalse($pager->hasNextPage());
    }

    public function testPrimaryKey()
    {
        $connection = $this->getConnection();

        $connection->query("
            create temporary table pkeytest (
                a integer,
                b integer,
                c integer,
                primary key (a, b)
            )
        ");

        $model = new ReadonlyModel(
            $connection,
            (new EntityStructure())
                ->setPrimaryKey(['a', 'b'])
                ->setEntityClass(DefaultEntity::class)
                ->setRelation('pkeytest')
                ->addField('a', 'int4')
                ->addField('b', 'int4')
                ->addField('c', 'int4')
        );

        // Ok now insert stuff using raw SQL, this only tests the readonly
        // implementation not the full-on
        $connection->query("
            insert into pkeytest (
                a, b, c
            )
            values (
                1, 1, 1
            ), (
                1, 2, 3
            ), (
                3, 2, 1
            ), (
                2, 1, 3
            ), (
                3, 1, 2
            )
        ");

        try {
            $model->findByPK(1);
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            $model->findByPK(['a' => 1, 'c' => 2]);
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            $model->findByPK(['b' => 1]);
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        $entity = $model->findByPK(['a' => 1, 'b' => 2]);
        $this->assertTrue($entity instanceof EntityInterface);
        $this->assertSame(1, $entity->get('a'));
        $this->assertSame(2, $entity->get('b'));
    }
}
