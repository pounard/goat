<?php

namespace Momm\Tests\ModelManager\Mock;

use Momm\ModelManager\EntityStructure;

class SomeStructure extends EntityStructure
{
    public function __construct()
    {
        $this
            ->setEntityClass(SomeEntity::class)
            ->setPrimaryKey(['id'])
            ->addField('foo', 'int4')
            ->addField('bar', 'varchar')
            ->addField('baz', 'timestamp')
        ;
    }
}
