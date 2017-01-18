<?php

namespace Goat\Tests\ModelManager;

use Goat\Core\Query\Where;
use Goat\ModelManager\Model;
use Goat\Tests\ConnectionAwareTest;
use Goat\Tests\ModelManager\Mock\SomeStructure;

class PgSQLModelTest extends ConnectionAwareTest
{
    protected function getDriver()
    {
        $this->markTestSkipped("this test is outdated");
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

        $model = new Model($connection, new SomeStructure());

        $this->assertSame(0, $model->countWhere());

        $reference = [
            ['foo' => -12,  'bar' => 'what',                              'baz' => new \DateTime('2012-05-22 08:30:00')],
            ['foo' => 24,   'bar' => 'cassoulet',                         'baz' => new \DateTime('2012-05-22 08:30:00')],
            ['foo' => -48,  'bar' => 'salut les amis',                    'baz' => new \DateTime('2012-05-22 08:30:00')],
            ['foo' => 96,   'bar' => 'this one will probably test like',  'baz' => new \DateTime('2012-05-22 08:30:00')],
            ['foo' => -192, /* Yes, baz is null */                        'baz' => new \DateTime('2012-05-22 08:30:00')],
        ];

        foreach ($reference as $row) {
            $entity = $model->createEntity($row);
            $model->insertOne($entity);
        }

        $entities = $model->findAll();
        $this->assertCount(5, $entities);

        $this->assertTrue($model->existWhere((new Where())->condition('foo', -12)));
        $this->assertTrue($model->existWhere((new Where())->condition('bar', 'salut les amis')));
        $this->assertTrue($model->existWhere((new Where())->condition('foo', 96)->condition('bar', 'this one will probably test like')));
        $this->assertFalse($model->existWhere((new Where())->condition('foo', 0)));
        $this->assertFalse($model->existWhere((new Where())->condition('foo', 24)->condition('bar', 'salut les amis')));
    }

    public function testInsertAll()
    {
        $connection = $this->getConnection();

        $connection->query("
            create temporary table some_entity_all (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp not null
            )
        ");

        $model = new Model($connection, (new SomeStructure())->setRelation('some_entity_all'));

        $reference = [
            ['foo' => -12,  'bar' => 'what',                              'baz' => new \DateTime('2012-05-22 08:30:00')],
            ['foo' => 24,   'bar' => 'cassoulet',                         'baz' => new \DateTime('2012-05-22 08:30:00')],
            ['foo' => -48,  'bar' => 'salut les amis',                    'baz' => new \DateTime('2012-05-22 08:30:00')],
            ['foo' => 96,   'bar' => 'this one will probably test like',  'baz' => new \DateTime('2012-05-22 08:30:00')],
            ['foo' => -192, /* Yes, baz is null */                        'baz' => new \DateTime('2012-05-22 08:30:00')],
        ];

        $all = [];
        foreach ($reference as $row) {
            $all[] = $model->createEntity($row);
        }

        $model->insertAll($all);

        $entities = $model->findAll();
        $this->assertCount(5, $entities);
    }

    public function testDelete()
    {
        $connection = $this->getConnection();

        $connection->query("
            create temporary table some_entity_delete (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp not null
            )
        ");

        $model = new Model($connection, (new SomeStructure())->setRelation('some_entity_delete'));

//         $reference = [
//             ['foo' => -12,  'bar' => 'what',                              'baz' => new \DateTime('2012-05-22 08:30:00')],
//             ['foo' => 24,   'bar' => 'cassoulet',                         'baz' => new \DateTime('2012-05-22 08:30:00')],
//             ['foo' => -48,  'bar' => 'salut les amis',                    'baz' => new \DateTime('2012-05-22 08:30:00')],
//             ['foo' => 96,   'bar' => 'this one will probably test like',  'baz' => new \DateTime('2012-05-22 08:30:00')],
//             ['foo' => -192, /* Yes, baz is null */                        'baz' => new \DateTime('2012-05-22 08:30:00')],
//         ];

//         $model->deleteWhere($where);
    }

    public function testUpdate()
    {
        $connection = $this->getConnection();

        $connection->query("
            drop table if exists some_entity
        ");
        $connection->query("
            create table some_entity (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp not null
            )
        ");

        $model = new Model($connection, new SomeStructure());

        $date = new \DateTime();
        $entity = $model->createEntity([
            'foo' => 1,
            'bar' => 'one',
            'baz' => $date,
        ]);
        $model->insertOne($entity);
        // And now check our object
        $this->assertTrue($entity->has('id'));
        $this->assertNotNull($entity->get('id'));
        $this->assertSame($entity->get('foo'), 1);
        $this->assertSame($entity->get('bar'), 'one');
        $this->assertEquals($entity->get('baz'), $date);

        $entity2 = $model->updateByPk($entity->get('id'), [
            'foo' => 2,
            'bar' => 'two',
        ]);
        // And check our object is modified
        $this->assertSame($entity2->get('foo'), 2);
        $this->assertSame($entity2->get('bar'), 'two');
        $this->assertEquals($entity2->get('baz'), $date);
    }
}
