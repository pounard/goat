<?php

namespace Goat\Core\Hydrator;

interface HydratorAwareInterface
{
    /**
     * Set hydrator map
     *
     * @param HydratorInterface $hydrator
     */
    public function setHydrator(HydratorInterface $hydrator);
}
