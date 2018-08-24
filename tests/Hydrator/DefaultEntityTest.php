<?php

namespace Goat\Tests\Hydrator;

use Goat\Hydrator\DefaultEntity;

/**
 * Tests default entity behaviour
 */
class DefaultEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests property access
     */
    public function testPropertyAccess()
    {
        $entity = new DefaultEntity(
            [
                'foo'   => 'foo',
                'bar'   => 845,
                'baz'   => null,
                'some'  => '',
            ],
            [
                'foo' => 'varchar',
                'bar' => 'int4',
                'baz' => 'timestamp',
            ]
        );

        // Test exists()
        $this->assertTrue($entity->exists('foo'));
        $this->assertTrue($entity->exists('bar'));
        $this->assertTrue($entity->exists('baz'));
        $this->assertFalse($entity->exists('status'));
        $this->assertFalse($entity->exists('cassoulet'));
        $this->assertFalse($entity->exists('other'));
        $this->assertSame(['foo' => 'foo', 'bar' => 845, 'baz' => null, 'some' => ''], $entity->getAll());

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

        // Tests types
        $this->assertSame('varchar', $entity->getType('foo'));
        $this->assertSame('int4', $entity->getType('bar'));
        $this->assertSame('timestamp', $entity->getType('baz'));
        $this->assertSame(DefaultEntity::DEFAULT_TYPE, $entity->getType('some'));
        $this->assertSame(['foo' => 'varchar', 'bar' => 'int4', 'baz' => 'timestamp', 'some' => DefaultEntity::DEFAULT_TYPE], $entity->getAllTypes());
        foreach (['status', 'cassoulet', 'other'] as $property) {
            try {
                $entity->getType($property);
                $this->fail("An exception should have been raised");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }
}
