<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Core\Client\ConnectionInterface;
use Goat\Mapper\MapperInterface;
use Goat\Mapper\TableMapper;

/**
 * Tests the selet based mapper
 */
class TableMapperTest extends AbstractMapperTest
{
    /**
     * {@inheritdoc}
     */
    protected function createMapper(ConnectionInterface $connection, string $class, array $primaryKey) : MapperInterface
    {
        return new TableMapper(
            $class,
            $primaryKey,
            $connection,
            [
                'relation' => 'some_entity',
                'alias' => 't',
                'joins' => [
                    [
                        'relation' => 'users',
                        'alias' => 'u',
                        'condition' => 'u.id = t.id_user',
                    ],
                ],
            ]
        );
    }
}
