<?php

namespace Momm\Tests\Core\Query;

use Momm\Core\Query\Projection;

class ProjectionTest extends \PHPUnit_Framework_TestCase
{
    use SqlTestTrait;

    public function testProjectionBasics()
    {
        $projection = (new Projection())
            ->setField('foo')
            ->setField('bar', null, 'int4')
            ->setField('baz', 'cassoulet', 'varchar')
            ->setField('roger', 'tabouret')
        ;

        $reference = <<<EOT
foo as foo,
bar as bar,
cassoulet as baz,
tabouret as roger
EOT;

        $this->assertSameSql($reference, (string)$projection);
    }

    public function testProjectionStatementAndTokenReplace()
    {
        $projection = (new Projection())
            ->setRelationAlias('some_table')
            ->setField('age', 'age(%:birthdate:%)')
            ->setField('birthdate')
            ->setField('total', 'count(%:id:%)')
        ;

        $reference = <<<EOT
age(some_table.birthdate) as age,
some_table.birthdate as birthdate,
count(some_table.id) as total
EOT;

        $this->assertSameSql($reference, (string)$projection);
    }

    public function testProjectionRelationAliasing()
    {
        $projection = (new Projection('my_relation'))
            ->setField('foo')
            ->setField('bar', null, 'int4')
        ;

        $reference = <<<EOT
my_relation.foo as foo,
my_relation.bar as bar
EOT;

        $this->assertSameSql($reference, $projection->format());

        $projection = (new Projection())
            ->setRelationAlias('and_another_one')
            ->setField('john')
            ->setField('doe')
        ;

        $reference = <<<EOT
and_another_one.john as john,
and_another_one.doe as doe
EOT;

        $this->assertSameSql($reference, (string)$projection);
    }
}
