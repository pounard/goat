<?php

declare(strict_types=1);

namespace Goat\Hydrator;

use Goat\Error\GoatError;

/**
 * Hydrates DefaultEntity instances
 *
 * This class is a convenience for people that don't want to create their own
 * classes but still need a minimum of logic upon it. You should probably not
 * use it, actually.
 */
final class DefaultEntityHydrator implements HydratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function createAndHydrateInstance(array $values, int $constructor = HydratorInterface::CONSTRUCTOR_LATE)
    {
        return new DefaultEntity($values);
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateObject(array $values, $object)
    {
        throw new GoatError(\sprintf("Can't hydrate a %s instance after creation, object is immutable", DefaultEntity::class));
    }

    /**
     * {@inheritdoc}
     */
    public function extractValues($object) : array
    {
        if (!$object instanceof EntityInterface) {
            throw new GoatError(\sprintf("Entity must implement %s, type %s given", EntityInterface::class, \gettype($object)));
        }

        return $object->getAll();
    }
}
