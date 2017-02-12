<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Core\Client\ConnectionInterface;
use Goat\Mapper\MapperInterface;
use Goat\Mapper\TableMapper;
use Goat\Mapper\WritableTableMapper;

/**
 * Tests the selet based mapper
 */
class TableMapperTest extends AbstractMapperTest
{
    /**
     * Get custom definition
     *
     * @return array
     */
    private function getTableMapperDefinition() : array
    {
        return [
            'relation' => 'some_entity',
            'alias' => 't',
            'joins' => [
                [
                    'relation' => 'users',
                    'alias' => 'u',
                    'condition' => 'u.id = t.id_user',
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createMapper(ConnectionInterface $connection, string $class, array $primaryKey) : MapperInterface
    {
        return new TableMapper($connection, $class, $primaryKey, $this->getTableMapperDefinition());
    }

    /**
     * {@inheritdoc}
     */
    protected function createWritableMapper(ConnectionInterface $connection, string $class, array $primaryKey) : MapperInterface
    {
        return new WritableTableMapper($connection, $class, $primaryKey, $this->getTableMapperDefinition());
    }
}
