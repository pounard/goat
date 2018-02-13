<?php

declare(strict_types=1);

namespace Goat\Testing;

use Goat\Converter\DefaultConverter;
use Goat\Driver\Dsn;
use Goat\Driver\PgSQL\ExtPgSQLConnection;
use Goat\Hydrator\HydratorMap;
use Goat\Runner\RunnerInterface;

/**
 * Only supporting ext_pgsql for now.
 *
 * @todo
 *   - provide helpers for dynamically determining which driver to choose
 *     depending on php env variables
 *   - provide a factory phpunit source method for drivers for testing
 *     more than one driver at a time
 *   - document it
 */
trait GoatTestTrait
{
    protected function createTemporaryDirectory() : string
    {
        $cacheDir = sys_get_temp_dir().'/'.uniqid('goat-hydrator-');

        if (file_exists($cacheDir)) {
            if (!is_dir($cacheDir)) {
                throw new \Exception(sprintf("the '%s' cache directory exists but is not a directory", $cacheDir));
            } else if (!is_writable($cacheDir)) {
                throw new \Exception(sprintf("the '%s' cache directory exists but is not writable", $cacheDir));
            }
        } else if (!mkdir($cacheDir)) {
            throw new \Exception(sprintf("could not create the '%s' cache directory", $cacheDir));
        }

        return $cacheDir;
    }

    /**
     * Create converter
     */
    protected function createConverter() : DefaultConverter
    {
        $map = new DefaultConverter();

        foreach (DefaultConverter::getDefautConverterMap() as $type => $data) {
            list($class, $aliases) = $data;

            $map->register($type, new $class(), $aliases);
        }

        return $map;
    }

    /**
     * Create object hydrator
     */
    protected function createHydrator() : HydratorMap
    {
        return new HydratorMap($this->createTemporaryDirectory());
    }

    /**
     * Get runner
     */
    protected function getRunner() : RunnerInterface
    {
        $connection = new ExtPgSQLConnection(new Dsn(getenv('EXT_PGSQL_DSN'), getenv('EXT_PGSQL_USERNAME'), getenv('EXT_PGSQL_PASSWORD')));
        $connection->setDebug(true);
        $connection->setConverter($this->createConverter());
        $connection->setHydratorMap($this->createHydrator());

        return $connection;
    }
}
