<?php

namespace Goat\Tests\Driver\Mock;

class DeleteSomeTableWithUser
{
    private $id;
    private $userId;
    private $name;
    private $bar;

    public function getId() : int
    {
        return $this->id;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }

    public function getUserName() : string
    {
        return $this->name;
    }

    public function getBar() : string
    {
        return $this->bar;
    }
}
