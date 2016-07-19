<?php

namespace Momm\Tests\ModelManager\Mock;

use Momm\ModelManager\EntityStructure;

class SomeStructure extends EntityStructure
{
    public function __construct()
    {
        $this
            ->setEntityClass(SomeEntity::class)
            ->setRelation('some_entity')
            ->setPrimaryKey(['id'])
            ->addField('id', 'serial')
            ->addField('foo', 'int4')
            ->addField('bar', 'varchar')
            ->addField('baz', 'timestamp')
        ;
    }
}
