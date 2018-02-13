<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Mapper\DefaultMapper;
use Goat\Mapper\MapperInterface;
use Goat\Mapper\WritableDefaultMapper;
use Goat\Mapper\WritableMapperInterface;
use Goat\Runner\RunnerInterface;

/**
 * Tests the default mapper
 */
class DefaultMapperTest extends AbstractMapperTest
{
    /**
     * {@inheritdoc}
     */
    protected function supportsJoin() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function createMapper(RunnerInterface $driver, string $class, array $primaryKey) : MapperInterface
    {
        return new DefaultMapper($driver, $class, $primaryKey, 'some_entity', 't', ['id', 'id_user', 'status', 'foo', 'bar', 'baz']);
    }

    /**
     * {@inheritdoc}
     */
    protected function createWritableMapper(RunnerInterface $driver, string $class, array $primaryKey) : WritableMapperInterface
    {
        return new WritableDefaultMapper($driver, $class, $primaryKey, 'some_entity', 't', ['id', 'id_user', 'status', 'foo', 'bar', 'baz']);
    }
}
