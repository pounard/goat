<?php

declare(strict_types=1);

namespace Goat\Testing;

use Goat\Converter\ConverterMap;
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
    /**
     * Create converter
     */
    private function createConverter() : ConverterMap
    {
        $map = new ConverterMap();

        foreach (ConverterMap::getDefautConverterMap() as $type => $data) {
            list($class, $aliases) = $data;

            $map->register($type, new $class(), $aliases);
        }

        return $map;
    }

    /**
     * Create object hydrator
     */
    private function createHydrator() : HydratorMap
    {
        $cacheDir = sys_get_temp_dir().'/'.uniqid('goat-test-');
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir)) {
                $this->markTestSkipped(sprintf("cannot create temporary folder %s", $cacheDir));
            }
        }

        return new HydratorMap($cacheDir);
    }

    /**
     * Get runner
     */
    final protected function getRunner() : RunnerInterface
    {
        $connection = new ExtPgSQLConnection(new Dsn(getenv('EXT_PGSQL_DSN'), getenv('EXT_PGSQL_USERNAME'), getenv('EXT_PGSQL_PASSWORD')));
        $connection->setDebug(true);
        $connection->setConverter($this->createConverter());
        $connection->setHydratorMap($this->createHydrator());

        return $connection;
    }
}
