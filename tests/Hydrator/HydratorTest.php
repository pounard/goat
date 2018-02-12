<?php

declare(strict_types=1);

namespace Goat\Tests\Hydrator;

use Goat\Hydrator\Configuration;
use Goat\Hydrator\HydratorInterface;
use Goat\Hydrator\HydratorMap;
use Goat\Testing\GoatTestTrait;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class HydratorTest extends \PHPUnit_Framework_TestCase
{
    use GoatTestTrait;

    /**
     * Test basics
     */
    public function testBasicFeatures()
    {
        $hydratorMap = new HydratorMap($this->createTemporaryDirectory());
        $hydrator = $hydratorMap->get(HydratedClass::class);

        $test1 = new HydratedClass();
        $this->assertTrue($test1->constructorHasRun);
        $this->assertFalse($test1->constructorHasRunWithData);

        $hydrator->hydrateObject(['foo' => 42, 'bar' => 'test', 'baz' => true], $test1);
        $this->assertSame(42, $test1->getFoo());
        $this->assertSame('test', $test1->getBar());
        $this->assertSame(true, $test1->getBaz());
        $this->assertTrue($test1->constructorHasRun);
        $this->assertFalse($test1->constructorHasRunWithData);

        $test2 = $hydrator->createAndHydrateInstance(['foo' => 666, 'bar' => 'pouet', 'baz' => false], HydratorInterface::CONSTRUCTOR_LATE);
        $this->assertSame(666, $test2->getFoo());
        $this->assertSame('pouet', $test2->getBar());
        $this->assertSame(false, $test2->getBaz());
        $this->assertTrue($test2->constructorHasRun);
        $this->assertTrue($test2->constructorHasRunWithData);

        $test3 = $hydrator->createAndHydrateInstance(['foo' => 118, 'bar' => 'cassoulet', 'baz' => false], HydratorInterface::CONSTRUCTOR_SKIP);
        $this->assertSame(118, $test3->getFoo());
        $this->assertSame('cassoulet', $test3->getBar());
        $this->assertSame(false, $test3->getBaz());
        $this->assertFalse($test3->constructorHasRun);
        $this->assertFalse($test3->constructorHasRunWithData);

        $test3 = $hydrator->createAndHydrateInstance(['foo' => 218, 'bar' => 'maroilles', 'baz' => true], HydratorInterface::CONSTRUCTOR_NORMAL);
        $this->assertSame(218, $test3->getFoo());
        $this->assertSame('maroilles', $test3->getBar());
        $this->assertSame(true, $test3->getBaz());
        $this->assertTrue($test3->constructorHasRun);
        $this->assertFalse($test3->constructorHasRunWithData);

        $values = $hydrator->extractValues($test3);
        $this->assertCount(8, $values);
        $this->assertSame(218, $values['foo']);
        $this->assertSame('maroilles', $values['bar']);
        $this->assertSame(true, $values['baz']);
    }

    /**
     * Test object nested hydration class name discovery using annotation works
     */
    public function testNestedAnnotedDiscovery()
    {
        // @todo I am sorry, I need to fix this
        $this->markTestIncomplete("this needs to be implemented properly");

        $hydratorMap = new HydratorMap($this->createTemporaryDirectory());

        //AnnotationRegistry::registerAutoloadNamespace("MyProject\Annotations", "/path/to/myproject/src");
        //AnnotationRegistry::registerLoader('class_exists');
        $annotationReader = new AnnotationReader();
        $configuration = new Configuration();
        $configuration->setAnnotationReader($annotationReader);

        $hydrator = $hydratorMap->get(HydratedClass::class);

        $values = [
            'annotedNestedInstance.miaw' => 42,
        ];

        /** @var \Goat\Tests\Hydrator\HydratedClass $instance */
        $instance = $hydrator->createAndHydrateInstance($values);
        $this->assertInstanceOf(HydratedParentClass::class, $instance->getAnnotedNestedInstance());
        $this->assertSame(42, $instance->getAnnotedNestedInstance()->getMiaw());
    }

    /**
     * Test object nesting hydration
     */
    public function testNesting()
    {
        $hydratorMap = new HydratorMap($this->createTemporaryDirectory());
        $hydratorMap->setConfiguration(new Configuration([
            HydratedClass::class => [
                // Test that manually defined properties do work
                'constructor' => HydratorInterface::CONSTRUCTOR_SKIP,
                // Tests correct behavior of the 'constructor' option
                'properties' => [
                    'someNestedInstance' => HydratedParentClass::class,
                ],
            ],
        ]));
        $hydrator = $hydratorMap->get(HydratedNestingClass::class);

        $values = [
            'ownProperty1' => 1,
            'ownProperty2' => 3,
            'nestedObject1.foo' => 5,
            'nestedObject1.bar' => 7,
            'nestedObject1.someNestedInstance.miaw' => 17,
            'nestedObject2.miaw' => 11,
        ];

        /** @var \Goat\Tests\Hydrator\HydratedNestingClass $nesting1 */
        $nesting1 = $hydrator->createAndHydrateInstance($values);
        $this->assertInstanceOf(HydratedNestingClass::class, $nesting1);
        $this->assertSame(1, $nesting1->getOwnProperty1());
        $this->assertSame(3, $nesting1->getOwnProperty2());
        $this->assertInstanceOf(HydratedClass::class, $nesting1->getNestedObject1());
        $this->assertFalse($nesting1->getNestedObject1()->constructorHasRun);
        $this->assertSame(5, $nesting1->getNestedObject1()->getFoo());
        $this->assertSame(7, $nesting1->getNestedObject1()->getBar());
        $this->assertInstanceOf(HydratedParentClass::class, $nesting1->getNestedObject2());
        $this->assertSame(11, $nesting1->getNestedObject2()->getMiaw());
        $this->assertInstanceOf(HydratedParentClass::class, $nesting1->getNestedObject1()->getSomeNestedInstance());
        $this->assertSame(17, $nesting1->getNestedObject1()->getSomeNestedInstance()->getMiaw());

        $nesting2 = new HydratedNestingClass();
        $hydrator->hydrateObject($values, $nesting2);
        $this->assertInstanceOf(HydratedNestingClass::class, $nesting2);
        $this->assertSame(1, $nesting2->getOwnProperty1());
        $this->assertSame(3, $nesting2->getOwnProperty2());
        $this->assertInstanceOf(HydratedClass::class, $nesting2->getNestedObject1());
        $this->assertFalse($nesting1->getNestedObject1()->constructorHasRun);
        $this->assertSame(5, $nesting2->getNestedObject1()->getFoo());
        $this->assertSame(7, $nesting2->getNestedObject1()->getBar());
        $this->assertInstanceOf(HydratedParentClass::class, $nesting2->getNestedObject2());
        $this->assertSame(11, $nesting2->getNestedObject2()->getMiaw());
        $this->assertInstanceOf(HydratedParentClass::class, $nesting1->getNestedObject1()->getSomeNestedInstance());
        $this->assertSame(17, $nesting1->getNestedObject1()->getSomeNestedInstance()->getMiaw());
    }
}
