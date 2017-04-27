<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Driver\DriverInterface;
use Goat\Mapper\MapperInterface;
use Goat\Mapper\SelectMapper;
use Goat\Mapper\WritableSelectMapper;

/**
 * Tests the selet based mapper
 */
class SelectMapperTest extends AbstractMapperTest
{
    /**
     * {@inheritdoc}
     */
    protected function createMapper(DriverInterface $connection, string $class, array $primaryKey) : MapperInterface
    {
        return new SelectMapper(
            $connection,
            $class,
            $primaryKey,
            $connection
                ->select('some_entity', 't')
                ->column('t.*')
                ->column('u.name')
                ->join('users', 'u.id = t.id_user', 'u')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createWritableMapper(DriverInterface $connection, string $class, array $primaryKey) : MapperInterface
    {
        return new WritableSelectMapper(
            $connection,
            $class,
            $primaryKey,
            $connection
                ->select('some_entity', 't')
                ->column('t.*')
                ->column('u.name')
                ->join('users', 'u.id = t.id_user', 'u')
        );
    }
}
