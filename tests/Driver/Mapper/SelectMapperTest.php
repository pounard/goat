<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Mapper\MapperInterface;
use Goat\Mapper\SelectMapper;
use Goat\Mapper\WritableSelectMapper;
use Goat\Runner\RunnerInterface;

/**
 * Tests the selet based mapper
 */
class SelectMapperTest extends AbstractMapperTest
{
    /**
     * {@inheritdoc}
     */
    protected function createMapper(RunnerInterface $driver, string $class, array $primaryKey) : MapperInterface
    {
        return new SelectMapper(
            $driver,
            $class,
            $primaryKey,
            $driver
                ->select('some_entity', 't')
                ->column('t.*')
                ->column('u.name')
                ->join('users', 'u.id = t.id_user', 'u')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createWritableMapper(RunnerInterface $driver, string $class, array $primaryKey) : MapperInterface
    {
        return new WritableSelectMapper(
            $driver,
            $class,
            $primaryKey,
            $driver
                ->select('some_entity', 't')
                ->column('t.*')
                ->column('u.name')
                ->join('users', 'u.id = t.id_user', 'u')
        );
    }
}
