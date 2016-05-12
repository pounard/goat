<?php

namespace Momm\Core;

use Momm\Core\Client\ConnectionInterface;
use Momm\Core\Converter\Converter;
use Momm\Core\Converter\ConverterInterface;
use Momm\Core\Converter\Impl\DecimalConverter;
use Momm\Core\Converter\Impl\IntegerConverter;
use Momm\Core\Converter\Impl\StringConverter;
use Momm\Core\Converter\Impl\TimestampConverter;

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
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        $this->prepare();
    }

    protected function buildConverter()
    {
        $default = new StringConverter();
        $default->setConnection($this->connection);

        return (new Converter())
            ->register(['varchar'], $default)
            // In MySQL there is no bytea, blob is more similar to text.
            ->register(['bytea'], $default)
            ->register(['int', 'int2', 'int4', 'int8', 'numeric'], new IntegerConverter())
            ->register(['float4', 'float8'], new DecimalConverter())
            ->register(['time', 'date', 'datetime', 'timestamp'], new TimestampConverter())
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
