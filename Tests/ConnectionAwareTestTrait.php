<?php

namespace Goat\Tests;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Client\Dsn;
use Goat\Core\Converter\Converter;
use Goat\Core\Converter\Impl\DecimalConverter;
use Goat\Core\Converter\Impl\IntegerConverter;
use Goat\Core\Converter\Impl\StringConverter;
use Goat\Core\Converter\Impl\TimestampConverter;
use Goat\Driver\PDO\MySQLConnection;
use Goat\Driver\PDO\PgSQLConnection;

trait ConnectionAwareTestTrait
{
    private $connection;
    private $converter;
    private $encoding;

    /**
     * Get driver name
     *
     * @return string
     */
    protected function getDriver()
    {
        return 'PGSQL';
    }

    /**
     * Create converter
     *
     * @return Converter
     */
    protected function createConverter(ConnectionInterface $connection)
    {
        $default = new StringConverter();
        $default->setEscaper($connection);

        return (new Converter())
            ->register(['varchar'], $default)
            // In MySQL there is no bytea, blob is more similar to text.
            ->register(['bytea'], $default)
            ->register(['integer' ,'int', 'int4', 'int8', 'serial'], new IntegerConverter())
            ->register(['float4', 'float8', 'double', 'decimal', 'numeric'], new DecimalConverter())
            ->register(['date', 'time', 'datetime', 'timestamp'], new TimestampConverter())
            ->setFallback($default)
        ;
    }

    /**
     * Create the connection object
     *
     * @param Dsn $dsn
     * @param string $username
     * @param string $password
     * @param string $encoding
     */
    protected function createConnection($encoding = 'utf8')
    {
        $driver = $this->getDriver();

        $variable = strtoupper($driver) . '_DSN';
        $hostname = getenv($variable);
        $username = getenv(strtoupper($driver) . '_USERNAME');
        $password = getenv(strtoupper($driver) . '_PASSWORD');

        if (!$hostname) {
            $this->markTestSkipped(sprintf("missing %s variable", $variable));
        }

        $dsn = new Dsn($hostname, $username, $password, $encoding);

        switch ($dsn->getDriver()) {

            case 'mysql':
                $connection = new MySQLConnection($dsn);
                break;

            case 'pgsql':
                $connection = new PgSQLConnection($dsn);
                break;

            default:
                throw new \InvalidArgumentException("%s driver is not supported yet", $dsn->getDriver());
        }

        $connection->setConverter($this->converter = $this->createConverter($connection));

        return $connection;
    }

    /**
     * Get connection
     *
     * @return \Goat\Driver\PDO\PgSQLConnection
     */
    final protected function getConnection($encoding = 'utf8')
    {
        if (null === $this->connection) {
            $this->connection = $this->createConnection($encoding);
            $this->encoding = $encoding;
        }

        if ($encoding !== $this->encoding) {
            $this->connection->setClientEncoding($encoding);
            $this->encoding = $encoding;
        }

        return $this->connection;
    }
}
