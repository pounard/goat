<?php

declare(strict_types=1);

namespace Goat\Mapper;

/**
 * Writable variant of the TableMapper class
 */
class WritableTableMapper extends TableMapper implements WritableMapperInterface
{
    use WritableMapperTrait;
}
