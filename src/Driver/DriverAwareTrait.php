<?php

declare(strict_types=1);

namespace Goat\Driver;

trait DriverAwareTrait
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Set connection
     *
     * @param DriverInterface $driver
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }
}
