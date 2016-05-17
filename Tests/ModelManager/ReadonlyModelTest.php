<?php

namespace Momm\Tests\ModelManager;

use Momm\Core\Client\PDO\PDOConnection;
use Momm\Core\Session;
use Momm\Core\Query\Where;
use Momm\ModelManager\ReadonlyModel;
use Momm\Tests\ModelManager\Mock\SomeStructure;

class ReadonlyModelTest extends \PHPUnit_Framework_TestCase
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

        $model = new ReadonlyModel($connection, new SomeStructure());

        $this->assertSame(0, $model->countWhere((new Where())->statement('1')));

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
    }
}
