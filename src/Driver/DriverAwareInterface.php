<?php

declare(strict_types=1);

namespace Goat\Driver;

interface DriverAwareInterface
{
    /**
     * Set connection
     *
     * @param DriverInterface $connection
     */
    public function setConnection(DriverInterface $connection);
}
