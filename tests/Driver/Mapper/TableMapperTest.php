<?php

declare(strict_types=1);

namespace Goat\Tests\Driver\Mapper;

use Goat\Driver\DriverInterface;
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
    protected function createMapper(DriverInterface $driver, string $class, array $primaryKey) : MapperInterface
    {
        return new TableMapper($driver, $class, $primaryKey, $this->getTableMapperDefinition());
    }

    /**
     * {@inheritdoc}
     */
    protected function createWritableMapper(DriverInterface $driver, string $class, array $primaryKey) : MapperInterface
    {
        return new WritableTableMapper($driver, $class, $primaryKey, $this->getTableMapperDefinition());
    }
}
