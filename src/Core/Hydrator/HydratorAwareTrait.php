<?php

declare(strict_types=1);

namespace Goat\Core\Hydrator;

trait HydratorAwareTrait
{
    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * Set hydrator map
     *
     * @param HydratorInterface $hydrator
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;
    }
}
