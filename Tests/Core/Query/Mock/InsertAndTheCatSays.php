<?php

namespace Goat\Tests\Core\Query\Mock;

class InsertAndTheCatSays
{
    private $id;
    private $miaw;

    public function getId() : int
    {
        return $this->id;
    }

    public function miaw() : string
    {
        return $this->miaw;
    }
}
