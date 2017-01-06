<?php

namespace Goat\Tests;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Client\Dsn;
use Goat\Core\Converter\ConverterMap;
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

        // Order of converters and types gives you the order in which they will
        // be guessed if no type is specified, go from the more complex to the
        // lesser to ensure there is no data loss in such case.
        return (new ConverterMap())
            ->register(['varchar'], $default)
            // In MySQL there is no bytea, blob is more similar to text.
            ->register(['bytea'], $default)
            ->register(['int8', 'int4', 'integer', 'int', 'serial'], new IntegerConverter())
            ->register(['float8', 'float4', 'double', 'decimal', 'numeric'], new DecimalConverter())
            ->register(['timestamp', 'datetime', 'date', 'time'], new TimestampConverter())
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
