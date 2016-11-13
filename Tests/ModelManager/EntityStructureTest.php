<?php

namespace Goat\Tests\ModelManager;

use Goat\ModelManager\EntityStructure;
use Goat\Tests\ModelManager\Mock\SomeEntity;
use Goat\Tests\ModelManager\Mock\SomeStructure;

class EntityStructureTest extends \PHPUnit_Framework_TestCase
{
    protected function doTestHydratation(EntityStructure $structure)
    {
        $this->assertSame(SomeEntity::class, $structure->getEntityClass());

        $entity = $structure->create();
        $this->assertTrue($entity instanceof SomeEntity);

        $now = new \DateTime();
        $entity = $structure->create([
            'id'  => 12,
            'foo' => 789,
            'bar' => 'bar',
            'baz' => $now,
        ]);

        $this->assertSame(12, $entity->get('id'));
        $this->assertSame(789, $entity->get('foo'));
        $this->assertSame('bar', $entity->get('bar'));
        $this->assertSame($now, $entity->get('baz'));

        // Ensures that uninitialized but defined fields exist
        $now = new \DateTime();
        $entity = $structure->create([
            'id'  => 12,
            'foo' => 789,
        ]);
        $this->assertSame(789, $entity->get('foo'));
        $this->assertTrue($entity->exists('bar'));
        $this->assertFalse($entity->has('bar'));
        $this->assertTrue($entity->exists('baz'));
        $this->assertFalse($entity->has('bar'));
            foreach (['status', 'cassoulet', 'other'] as $property) {
             try {
                  $entity->get($property);
                  $this->fail("An exception should have been raised");
             } catch (\InvalidArgumentException $e) {
                  $this->assertTrue(true);
             }
        }

        // Now ensures that added custom field don't cause exceptions, but
        // unknown one still do
        $entity = $structure->create([
            'id'  => 12,
            'foo' => 789,
            'status'  => 'pouet'
        ]);
        $this->assertTrue($entity->exists('status'));
        $this->assertTrue($entity->has('status'));
        foreach (['cassoulet', 'other'] as $property) {
            try {
                $entity->get($property);
                $this->fail("An exception should have been raised");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testStaticStructureHydratation()
    {
        $this->doTestHydratation(new SomeStructure());
    }

    public function testFlexibleStructureHydratation()
    {
        $this->doTestHydratation(
            (new EntityStructure())
                ->setEntityClass(SomeEntity::class)
                ->setPrimaryKey(['id'])
                ->addField('foo', 'int4')
                ->addField('bar', 'varchar')
                ->addField('baz', 'timestamp')
        );
    }
}
