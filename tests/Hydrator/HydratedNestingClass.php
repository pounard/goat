<?php

declare(strict_types=1);

namespace Goat\Tests\Hydrator;

final class HydratedNestingClass
{
    public $constructorHasRun = false;

    private $ownProperty1;
    private $ownProperty2;
    private $nestedObject1;
    private $nestedObject2;

    public function __construct()
    {
        $this->constructorHasRun = true;
    }

    public function getOwnProperty1()
    {
        return $this->ownProperty1;
    }

    public function getOwnProperty2()
    {
        return $this->ownProperty2;
    }

    /**
     * @return HydratedClass
     */
    public function getNestedObject1()
    {
        return $this->nestedObject1;
    }

    /**
     * @return HydratedParentClass
     */
    public function getNestedObject2()
    {
        return $this->nestedObject2;
    }
}
