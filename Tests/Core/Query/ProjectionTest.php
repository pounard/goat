<?php

namespace Momm\Tests\Core\Query;

use Momm\Core\Query\Projection;

class ProjectionTest extends \PHPUnit_Framework_TestCase
{
    use SqlTestTrait;

    public function testProjection()
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

        // Remove all whitespaces, and lowercase everything to ensure our
        // generation cruft don't change trigger false negatives
        $this->assertSameSql($reference, (string)$projection);
    }
}
