<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Core\Client\ConnectionInterface;
use Goat\Mapper\MapperInterface;
use Goat\Mapper\SelectMapper;

/**
 * Tests the selet based mapper
 */
class SelectMapperTest extends AbstractMapperTest
{
    /**
     * {@inheritdoc}
     */
    protected function createMapper(ConnectionInterface $connection, string $class, array $primaryKey) : MapperInterface
    {
        return new SelectMapper(
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
