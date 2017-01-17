<?php

declare(strict_types=1);

namespace Goat\Tests\Core\Hydrator;

final class HydratedClass extends HydratedParentClass
{
    private static $miaw;
    protected static $waf;
    public static $moo;

    public $constructorHasRun = false;
    public $constructorHasRunWithData = false;

    private $foo;
    protected $bar;
    public $baz;

    public function __construct()
    {
        if ($this->foo) {
            $this->constructorHasRunWithData = true;
        }
        $this->constructorHasRun = true;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }

    public function getBaz()
    {
        return $this->baz;
    }
}
