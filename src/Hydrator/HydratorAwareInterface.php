<?php

declare(strict_types=1);

namespace Goat\Hydrator;

interface HydratorAwareInterface
{
    /**
     * Set hydrator map
     *
     * @param HydratorInterface $hydrator
     */
    public function setHydrator(HydratorInterface $hydrator);
}
