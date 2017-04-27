<?php

declare(strict_types=1);

namespace Goat\Hydrator;

use Goat\Mapper\Entity\DefaultEntity;
use Goat\Core\Error\GoatError;

/**
 * Hydrates DefaultEntity instances
 *
 * This class is a convenience for people that don't want to create their own
 * classes but still need a minimum of logic upon it. You should probably not
 * use it, actually.
 */
class DefaultEntityHydrator implements HydratorInterface
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
        throw new GoatError(sprintf("Can't hydrate a %s instance after creation, object is immutable", DefaultEntity::class));
    }
}
