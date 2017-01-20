<?php

declare(strict_types=1);

namespace Goat\Mapper;

/**
 * Writable variant of the SelectMapper class
 */
class WritableSelectMapper extends SelectMapper implements WritableMapperInterface
{
    use WritableMapperTrait;
}
