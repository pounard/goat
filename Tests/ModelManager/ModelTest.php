<?php

namespace Momm\Tests\ModelManager;

use Momm\Core\Client\PDO\PDOConnection;
use Momm\Core\Session;
use Momm\Core\Query\Where;
use Momm\ModelManager\Model;
use Momm\Tests\ModelManager\Mock\SomeStructure;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (!getenv('MYSQL_DSN')) {
            $this->markTestSkipped("Please set-up the MYSQL_DSN environment variable");
        }
    }

    public function testReadOperations()
    {
        $connection = new PDOConnection(getenv('MYSQL_DSN'), getenv('MYSQL_USERNAME'), getenv('MYSQL_PASSWORD'));
        new Session($connection); // This will register default converters

        $connection->query("
            create temporary table some_entity (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp not null
            )
        ");

        $model = new Model($connection, new SomeStructure());

        $this->assertSame(0, $model->countWhere((new Where())->statement('1')));

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
    }
}
