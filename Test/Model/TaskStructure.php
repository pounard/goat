<?php

namespace Momm\Test\Model;

use PommProject\ModelManager\Model\RowStructure;

class TaskStructure extends RowStructure
{
    public function __construct()
    {
        $this
            ->setRelation('momm.task')
            ->setPrimaryKey(['id'])
            ->addField('id', 'int4')
            ->addField('is_public', 'int4')
            ->addField('ts_created', 'datetime')
            ->addField('ts_deadline', 'datetime')
            ->addField('user_id', 'int4')
            ->addField('user_name', 'varchar')
            ->addField('description', 'text')
        ;
    }
}
