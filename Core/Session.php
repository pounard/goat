<?php

namespace Goat\Core;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Converter\Converter;
use Goat\Core\Converter\ConverterInterface;
use Goat\Core\Converter\Impl\DecimalConverter;
use Goat\Core\Converter\Impl\IntegerConverter;
use Goat\Core\Converter\Impl\StringConverter;
use Goat\Core\Converter\Impl\TimestampConverter;
use Goat\Driver\PDO\PDOConnection;

/**
 * Session object is not mandatory, but stands as a commodity for whoever has
 * no advanced service registration using a higher framework; it aims to provide
 * auto-configuration and a centralized way to fetch components.
 *
 * @todo
 *   - connection auto-configuration depending on provided dsn
 *   - ?
 */
class Session
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var ConverterInterface|Converter
     */
    protected $conterter;

    /**
     * Default constructor
     *
     * @param string|ConnectionInterface $connection
     */
    public function __construct($connection)
    {
        if ($connection instanceof ConnectionInterface) {
            $this->connection = $connection;
        } else {
            // @todo
            //   - as of now, we have only one implementation; PDO mysql but
            //     later we should auto discover amongst implementations
            //     dependending on the provided dsn
            if (is_string($connection)) {
                $this->connection = new PDOConnection($connection);
            } else if (is_array($connection)) {
                $this->connection = new PDOConnection($connection['dsn'], $connection['username'], $connection['password']);
            } else {
                throw new \InvalidArgumentException("invalid connection or dsn provided");
            }
        }

        $this->prepare();
    }

    /**
     * Get connection
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Build default converters instances
     */
    protected function buildConverter()
    {
        $default = new StringConverter();
        $default->setEscaper($this->connection);

        return (new Converter())
            ->register(['varchar'], $default)
            // In MySQL there is no bytea, blob is more similar to text.
            ->register(['bytea'], $default)
            ->register(['int', 'int2', 'int4', 'int8', 'numeric', 'serial'], new IntegerConverter())
            ->register(['float4', 'float8'], new DecimalConverter())
            ->register(['date', 'time', 'datetime', 'timestamp'], new TimestampConverter())
            ->setFallback($default)
        ;
    }

    /**
     * Prepare instance on build, if you need to do specific stuff upon init
     * this is the right method to override
     */
    protected function prepare()
    {
        $this->conterter = $this->buildConverter();
        $this->connection->setConverter($this->conterter);
    }
}
