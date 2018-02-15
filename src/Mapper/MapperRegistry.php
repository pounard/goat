<?php

declare(strict_types=1);

namespace Goat\Mapper;

interface MapperRegistryInterface
{
    /**
     * Default/none namespace
     */
    const NAMESPACE_DEFAULT = 'Default';

    /**
     * Is entity class supported
     *
     * @param string $className
     *
     * @return bool
     */
    public function isEntityClassSupported(string $className) : bool;

    /**
     * Find mapper
     *
     * @param string $name
     *   Either a mapper name or an entity class name
     *
     * @throws MapperNotFoundError
     *   If the mapper does not exists
     *
     * @return MapperInterface
     */
    public function getMapper(string $name) : WritableMapperInterface;
}
