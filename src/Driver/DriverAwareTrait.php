<?php

declare(strict_types=1);

namespace Goat\Driver;

trait DriverAwareTrait
{
    /**
     * @var DriverInterface
     */
    protected $connection;

    /**
     * Set connection
     *
     * @param DriverInterface $connection
     */
    public function setConnection(DriverInterface $connection)
    {
        $this->connection = $connection;
    }
}
