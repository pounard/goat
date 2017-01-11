<?php

namespace Goat\Tests\Core\Hydrator;

use Goat\Core\Hydrator\HydratorMap;

class HydratorTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicFeatures()
    {
        $hydratorMap = new HydratorMap(__DIR__ . '/../../../cache/hydrator');
        $hydrator = $hydratorMap->get(HydratedClass::class);

        $test1 = new HydratedClass();
        $this->assertTrue($test1->constructorHasRun);
        $this->assertFalse($test1->constructorHasRunWithData);

        $hydrator->hydrateObject(['foo' => 42, 'bar' => 'test', 'baz' => true], $test1);
        $this->assertSame(42, $test1->getFoo());
        $this->assertSame('test', $test1->getBar());
        $this->assertSame(true, $test1->getBaz());
    }
}
