<?php

namespace Goat\Tests\ModelManager;

use Goat\ModelManager\DefaultEntity;

class DefaultEntityTest extends \PHPUnit_Framework_TestCase
{
    public function testPropertyAccess()
    {
        $entity = new DefaultEntity();

        $entity->defineAll([
            'foo' => 'foo',
            'bar' => 845,
            'baz' => null,
        ]);

        // Test exists()
        $this->assertTrue($entity->exists('foo'));
        $this->assertTrue($entity->exists('bar'));
        $this->assertTrue($entity->exists('baz'));
        $this->assertFalse($entity->exists('status'));
        $this->assertFalse($entity->exists('cassoulet'));
        $this->assertFalse($entity->exists('other'));

        // Test get()
        $this->assertSame('foo', $entity->get('foo'));
        $this->assertSame(845, $entity->get('bar'));
        $this->assertSame(null, $entity->get('baz'));
        foreach (['status', 'cassoulet', 'other'] as $property) {
             try {
                  $entity->get($property);
                  $this->fail("An exception should have been raised");
             } catch (\InvalidArgumentException $e) {
                  $this->assertTrue(true);
             }
        }

        // Test has()
        $this->assertTrue($entity->has('foo'));
        $this->assertTrue($entity->has('bar'));
        $this->assertFalse($entity->has('baz'));
        foreach (['status', 'cassoulet', 'other'] as $property) {
            try {
                $entity->has($property);
                $this->fail("An exception should have been raised");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }

        // Test remove()
        $entity->remove('foo');
        $this->assertFalse($entity->has('foo'));
        $this->assertSame(null, $entity->get('foo'));
        foreach (['status', 'cassoulet', 'other'] as $property) {
            try {
                $entity->remove($property);
                $this->fail("An exception should have been raised");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }

        // Test set()
        $entity->set('bar', 666);
        $this->assertTrue($entity->has('bar'));
        $this->assertSame(666, $entity->get('bar'));
        $entity->set('foo', 42);
        $this->assertTrue($entity->has('foo'));
        $this->assertSame(42, $entity->get('foo'));
        foreach (['status', 'cassoulet', 'other'] as $property) {
            try {
                $entity->set($property, 'some_value');
                $this->fail("An exception should have been raised");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }
}
