<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Runner\RunnerInterface;

/**
 * Driver interface
 */
interface DriverInterface extends RunnerInterface
{
    /**
     * Get database server information
     *
     * @return string[]
     */
    public function getDatabaseInfo() : array;

    /**
     * Get database server name
     *
     * @return string
     */
    public function getDatabaseName() : string;

    /**
     * Get driver name
     *
     * @return string
     */
    public function getDriverName() : string;

    /**
     * Get database version if found
     *
     * @return string
     */
    public function getDatabaseVersion() : string;

    /**
     * Close connection
     */
    public function close();

    /**
     * Truncate given tables (warning, it does it right away)
     *
     * @todo
     *   - move this out into a ddl specific object
     *   - SQL 92 standard is about one table at a time, PgSQL can do multiple at once
     *
     * @param string|string[] $relationNames
     *   Either one or more table names
     */
    public function truncateTables($relationNames);

    /**
     * Set connection encoding
     *
     * @param string $encoding
     */
    public function setClientEncoding(string $encoding);
}
