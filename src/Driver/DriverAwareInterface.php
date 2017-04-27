<?php

declare(strict_types=1);

namespace Goat\Driver;

interface DriverAwareInterface
{
    /**
     * Set driver
     *
     * @param DriverInterface $driver
     */
    public function setDriver(DriverInterface $driver);
}
