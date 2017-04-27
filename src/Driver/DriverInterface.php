<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Converter\ConverterAwareInterface;
use Goat\Core\DebuggableInterface;
use Goat\Hydrator\HydratorMap;
use Goat\Query\QueryFactoryInterface;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\RunnerInterface;

/**
 * Driver interface
 */
interface DriverInterface extends ConverterAwareInterface, DebuggableInterface, RunnerInterface, QueryFactoryInterface
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
     * Does the backend supports RETURNING clauses
     *
     * @return bool
     */
    public function supportsReturning() : bool;

    /**
     * Does the backend supports defering constraints
     *
     * @return bool
     */
    public function supportsDeferingConstraints() : bool;

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

    /**
     * Get SQL formatter
     *
     * @return FormatterInterface
     */
    public function getFormatter() : FormatterInterface;

    /**
     * Get SQL escaper
     *
     * @return EscaperInterface
     */
    public function getEscaper() : EscaperInterface;

    /**
     * Set hydrator map
     *
     * @param HydratorMap $hydratorMap
     */
    public function setHydratorMap(HydratorMap $hydratorMap);
}
