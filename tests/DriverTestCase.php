<?php

declare(strict_types=1);

namespace Goat\Tests;

use Goat\Driver\DriverInterface;
use Goat\Driver\Dsn;
use Goat\Driver\Session;
use Goat\Driver\Drupal7\Drupal7Runner;
use Goat\Driver\Profiling\ProfilingDriverProxy;
use Goat\Runner\RunnerInterface;
use Goat\Testing\GoatTestTrait;

/**
 * Single driver test case
 */
abstract class DriverTestCase extends \PHPUnit_Framework_TestCase
{
    use GoatTestTrait;

    /**
     * Get known drivers
     *
     * @return array
     */
    private function getKnownDrivers() : array
    {
        return [
            'pdo_mysql' => \Goat\Driver\PDO\PDOMySQLConnection::class,
            'pdo_pgsql' => \Goat\Driver\PDO\PDOPgSQLConnection::class,
            'ext_pgsql' => \Goat\Driver\ExtPgSQL\ExtPgSQLConnection::class
        ];
    }

    /**
     * Bootstrap Drupal database
     *
     * @param string $drupalKey
     * @param string $path
     * @param string $host
     * @param string $username
     * @param string $password
     *
     * @return null|\DatabaseConnection
     */
    private function bootstrapDrupal7Database($drupalKey, $path, $host, $username, $password)
    {
          // Skip test
          if (empty($path) || empty($host)) {
              return null;
          }

          $normalized = new Dsn($host, $username, $password);
          $target = $path . '/includes/database/database.inc';

          if (!file_exists($target) || !is_readable($target)) {
              throw new \Exception(sprintf("Drupal path does not exists or is not readable: '%s'", $target));
          }

          require_once $target;
          if (!class_exists('\Database')) {
              throw new \Exception(sprintf("Target file did not load the Drupal \\Database class: '%s'", $target));
          }

          if (!defined('DRUPAL_ROOT')) {
              define('DRUPAL_ROOT', $path);
          }

          // @todo following code should be moved into the runner

          // Force Drupal database component to close existing connections else
          // tests with temp tables will keep the same session and crash
          \Database::removeConnection($drupalKey);

          // Force Drupal database component to bootstrap
          $options = [
              'database'  => $normalized->getDatabase(),
              'username'  => $normalized->getUsername(),
              'password'  => $normalized->getPassword(),
              'host'      => $normalized->getHost(),
              'port'      => $normalized->getPort(),
              'driver'    => $normalized->getDriver(),
              'prefix'    => '',
          ];

          switch ($normalized->getDriver()) {

              case 'mysql':
                  // Drupal is too strict with some type conversions default
                  // options, let's make it more flexible
                  $options['init_commands'] = [
                      // Removed STRICT_TRANS_TABLES,STRICT_ALL_TABLES else our
                      // datetime converters cannot work, also remove
                      // NO_ZERO_IN_DATE,NO_ZERO_DATE
                      // @todo restore this whenever we have dynamic converters
                      //   depending upon the driver
                      'sql_mode' => "SET sql_mode = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'",
                  ];
                  break;
          }

          \Database::addConnectionInfo($drupalKey, 'default', $options);

          return new Drupal7Runner(\Database::getConnection('default', $drupalKey));
    }

    /**
     * Get known drivers
     *
     * @return callable[]
     */
    private function getKnownRunners() : array
    {
        return [
            'drupal7_mysql' => function () {
                return $this->bootstrapDrupal7Database('goat_drupal7_mysql', getenv('DRUPAL7_MYSQL_PATH'), getenv('DRUPAL7_MYSQL_DSN'), getenv('DRUPAL7_MYSQL_USERNAME'), getenv('DRUPAL7_MYSQL_PASSWORD'));
            },
            'drupal7_pgsql' => function () {
                return $this->bootstrapDrupal7Database('goat_drupal7_pgsql', getenv('DRUPAL7_PGSQL_PATH'), getenv('DRUPAL7_PGSQL_DSN'), getenv('DRUPAL7_PGSQL_USERNAME'), getenv('DRUPAL7_PGSQL_PASSWORD'));
            },
        ];
    }

    /**
     * @var DriverInterface[]
     */
    private $drivers = [];

    /**
     * @var RunnerInterface[]
     */
    private $runners = [];

    /**
     * Create test case schema
     */
    protected function createTestSchema(RunnerInterface $driver)
    {
    }

    /**
     * Create test case schema
     */
    protected function createTestData(RunnerInterface $driver)
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

        foreach ($this->getKnownDrivers() as $driverName => $class) {
            $ret[] = [$driverName, $class];
        }

        foreach (array_keys($this->getKnownRunners()) as $runnerName) {
            $ret[] = [$runnerName, 'this_is_a_runner'];
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
     * Create the connection object as driver, fail if does not exists
     *
     * @param string $driver
     * @param string $class
     *
     * @return DriverInterface
     */
    final protected function createDriver(string $driver, string $class) : DriverInterface
    {
        if ('this_is_a_runner' === $class) {
            // We got ourselves a runner, just skip the test
            $this->markTestSkipped(sprintf("%s is a runner, not a driver", $driver));
        }

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
        $driver->setConverter($this->createConverter());
        $driver->setHydratorMap($this->createHydrator());

        $this->createTestSchema($driver);
        $this->createTestData($driver);

        return $this->drivers[] = new ProfilingDriverProxy(new Session($driver));
    }

    /**
     * Create the connection object as a runner
     *
     * @param string $runner
     * @param string $class
     *
     * @return RunnerInterface
     */
    final protected function createRunner(string $runner, string $class) : RunnerInterface
    {
        if ('this_is_a_runner' !== $class) {
            // We got ourselves a driver
            return $this->createDriver($runner, $class);
        }

        $runners = $this->getKnownRunners();

        if (!isset($runners[$runner])) {
            throw new \InvalidArgumentException(sprintf("Runner '%s' does not exist", $runner));
        }

        $instance = call_user_func($runners[$runner]);
        if (!$instance instanceof RunnerInterface) {
            $this->markTestSkipped(sprintf("Runner configuration for '%s' is missing, please see default phpunit.xml.dist file for additional environment variables", $runner));
        }

        $instance->setConverter($this->createConverter());
        $instance->setHydratorMap($this->createHydrator());

        $this->createTestSchema($instance);
        $this->createTestData($instance);

        return $this->runners[] = $instance;
    }
}
