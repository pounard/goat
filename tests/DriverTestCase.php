<?php

declare(strict_types=1);

namespace Goat\Tests;

use Goat\Converter\ConverterMap;
use Goat\Core\Hydrator\HydratorMap;
use Goat\Core\Profiling\ProfilingDriverProxy;
use Goat\Core\Session\Dsn;
use Goat\Core\Session\Session;
use Goat\Driver\DriverInterface;

/**
 * Single driver test case
 */
abstract class DriverTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Get known drivers
     *
     * @return array
     */
    public static function getKnownDrivers() : array
    {
        return [
            'pdo_mysql' => \Goat\Driver\PDO\PDOMySQLConnection::class,
            'pdo_pgsql' => \Goat\Driver\PDO\PDOPgSQLConnection::class,
            'ext_pgsql' => \Goat\Driver\PgSQL\ExtPgSQLConnection::class
        ];
    }

    /**
     * @var DriverInterface[]
     */
    private $drivers = [];

    /**
     * Create test case schema
     */
    protected function createTestSchema(DriverInterface $driver)
    {
    }

    /**
     * Create test case schema
     */
    protected function createTestData(DriverInterface $driver)
    {
    }

    /**
     * Create drivers for testing
     *
     * @return array
     */
    public function driverDataSource() : array
    {
        $ret = [];

        foreach (self::getKnownDrivers() as $driverName => $class) {
            $ret[] = [$driverName, $class];
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        foreach ($this->drivers as $driver) {
            $driver->close();
        }

        $this->drivers = [];
    }

    /**
     * Create converter
     *
     * @param DriverInterface $driver
     *
     * @return ConverterMap
     */
    final protected function createConverter(DriverInterface $driver) : ConverterMap
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
     *
     * @param DriverInterface $driver
     *
     * @return HydratorMap
     */
    final protected function createHydrator(DriverInterface $driver) : HydratorMap
    {
        return new HydratorMap(__DIR__ . '/../cache/hydrator');
    }

    /**
     * Create the connection object
     *
     * @param string $driver
     * @param string $class
     *
     * @return DriverInterface
     */
    final protected function createDriver(string $driver, string $class) : DriverInterface
    {
        $variable = strtoupper($driver) . '_DSN';
        $hostname = getenv($variable);
        $username = getenv(strtoupper($driver) . '_USERNAME');
        $password = getenv(strtoupper($driver) . '_PASSWORD');

        if (!$hostname) {
            throw new \InvalidArgumentException(sprintf("Parameter '%s' for driver '%s' is not configured", $variable, $driver));
        }

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf("Class '%s' for driver '%s' does not exists", $class, $driver));
        }
        if (!is_subclass_of($class, DriverInterface::class)) {
            throw new \InvalidArgumentException(sprintf("Class '%s' for driver '%s' does not implement '%s'", $class, $driver, DriverInterface::class));
        }

        $dsn = new Dsn($hostname, $username, $password);

        /** @var \Goat\Driver\DriverInterface $driver */
        $driver = new $class($dsn);
        $driver->setConverter($this->createConverter($driver));
        $driver->setHydratorMap($this->createHydrator($driver));

        $this->createTestSchema($driver);
        $this->createTestData($driver);

        return $this->drivers[] = new ProfilingDriverProxy(new Session($driver));
    }
}
