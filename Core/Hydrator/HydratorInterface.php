<?php

namespace Goat\Core\Hydrator;

/**
 * Hydrates objects
 */
interface HydratorInterface
{
    const CONSTRUCTOR_NORMAL = 0;
    const CONSTRUCTOR_SKIP = 1;
    const CONSTRUCTOR_LATE = 2;

    /**
     * Create object instance then hydrate it
     *
     * @param array $values
     * @param string $class
     *
     * @return object
     *   The new instance
     */
    public function createAndHydrateInstance(array $values, int $constructor = HydratorInterface::CONSTRUCTOR_LATE);

    /**
     * Hydrate object instance in place
     *
     * @param array $values
     * @param object $object
     */
    public function hydrateObject(array $values, $object);
}
