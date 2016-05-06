<?php

namespace Momm\Test\Model;

use Momm\ModelManager\Model\Model;

/**
 * Album model
 */
class TaskModel extends Model
{
    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->structure = new TaskStructure();
        $this->flexible_entity_class = Task::class;
    }
}
