<?php

declare(strict_types=1);

namespace Goat\Tests;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Client\Dsn;
use Goat\Core\Client\Session;
use Goat\Core\Converter\ConverterMap;
use Goat\Core\EventDispatcher\EventEmitterConnectionProxy;
use Goat\Core\Hydrator\HydratorMap;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    static public function getKnownDrivers() : array
    {
        return [
            'pdo_mysql' => \Goat\Driver\PDO\MySQLConnection::class,
            'pdo_pgsql' => \Goat\Driver\PDO\PgSQLConnection::class,
            'ext_pgsql' => \Goat\Driver\PgSQL\PgSQLConnection::class
        ];
    }

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Create test case schema
     */
    protected function createTestSchema(ConnectionInterface $connection)
    {
    }

    /**
     * Create test case schema
     */
    protected function createTestData(ConnectionInterface $connection)
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

        foreach (self::getKnownDrivers() as $driver => $class) {
            $ret[] = [$driver, $class];
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->eventDispatcher = null;
    }

    /**
     * Get event dispatcher
     *
     * @return EventDispatcherInterface
     */
    final protected function getEventDispatcher()
    {
        if (!$this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     * Create converter
     *
     * @param ConnectionInterface $connection
     *
     * @return ConverterMap
     */
    final protected function createConverter(ConnectionInterface $connection) : ConverterMap
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
     * @param ConnectionInterface $connection
     *
     * @return HydratorMap
     */
    final protected function createHydrator(ConnectionInterface $connection) : HydratorMap
    {
        return new HydratorMap(__DIR__ . '/../cache/hydrator');
    }

    /**
     * Create the connection object
     *
     * @param string $driver
     * @param string $class
     *
     * @return ConnectionInterface
     */
    final protected function createConnection(string $driver, string $class) : ConnectionInterface
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
        if (!is_subclass_of($class, ConnectionInterface::class)) {
            throw new \InvalidArgumentException(sprintf("Class '%s' for driver '%s' does not implement '%s'", $class, $driver, ConnectionInterface::class));
        }

        $dsn = new Dsn($hostname, $username, $password);

        /** @var \Goat\Core\Client\ConnectionInterface $connection */
        $connection = new $class($dsn);
        $connection->setConverter($this->createConverter($connection));
        $connection->setHydratorMap($this->createHydrator($connection));

        $this->createTestSchema($connection);
        $this->createTestData($connection);

        return new EventEmitterConnectionProxy(new Session($connection), $this->getEventDispatcher());
    }
}
