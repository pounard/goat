<?php

namespace Goat\Tests;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Client\Dsn;
use Goat\Core\Converter\ConverterMap;
use Goat\Core\Converter\Impl\BooleanConverter;
use Goat\Core\Converter\Impl\DecimalConverter;
use Goat\Core\Converter\Impl\IntegerConverter;
use Goat\Core\Converter\Impl\StringConverter;
use Goat\Core\Converter\Impl\TimestampConverter;
use Goat\Core\EventDispatcher\EventEmitterConnectionProxy;
use Goat\Driver\PDO\MySQLConnection;
use Goat\Driver\PDO\PgSQLConnection;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Goat\Core\Client\Session;

abstract class ConnectionAwareTest extends \PHPUnit_Framework_TestCase
{
    private $connection;
    private $converter;
    private $encoding;
    private $eventDispatcher;

    /**
     * Get driver name
     *
     * @return string
     */
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }

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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $connection = $this->getConnection();
        $this->createTestSchema($connection);
        $this->createTestData($connection);
    }

    protected function tearDown()
    {
        $this->connection = null;
        $this->converter = null;
        $this->encoding = null;
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
     * @return Converter
     */
    final protected function createConverter(ConnectionInterface $connection)
    {
        $default = new StringConverter();
        $default->setEscaper($connection);

        /*
         * Mapping from PostgreSQL 9.2
         *
        bigint 	int8 	signed eight-byte integer
        bigserial 	serial8 	autoincrementing eight-byte integer
        ## bit [ (n) ] 	  	fixed-length bit string
        ## bit varying [ (n) ] 	varbit 	variable-length bit string
        boolean 	bool 	logical Boolean (true/false)
        ## box 	  	rectangular box on a plane
        bytea 	  	binary data ("byte array")
        character [ (n) ] 	char [ (n) ] 	fixed-length character string
        character varying [ (n) ] 	varchar [ (n) ] 	variable-length character string
        ## cidr 	  	IPv4 or IPv6 network address
        ## circle 	  	circle on a plane
        date 	  	calendar date (year, month, day)
        double precision 	float8 	double precision floating-point number (8 bytes)
        ## inet 	  	IPv4 or IPv6 host address
        integer 	int, int4 	signed four-byte integer
        ## interval [ fields ] [ (p) ] 	  	time span
        ## json 	  	JSON data
        ## line 	  	infinite line on a plane
        ## lseg 	  	line segment on a plane
        ## macaddr 	  	MAC (Media Access Control) address
        ## money 	  	currency amount
        numeric [ (p, s) ] 	decimal [ (p, s) ] 	exact numeric of selectable precision
        ## path 	  	geometric path on a plane
        ## point 	  	geometric point on a plane
        ## polygon 	  	closed geometric path on a plane
        real 	float4 	single precision floating-point number (4 bytes)
        smallint 	int2 	signed two-byte integer
        smallserial 	serial2 	autoincrementing two-byte integer
        serial 	serial4 	autoincrementing four-byte integer
        text 	  	variable-length character string
        time [ (p) ] [ without time zone ] 	  	time of day (no time zone)
        time [ (p) ] with time zone 	timetz 	time of day, including time zone
        timestamp [ (p) ] [ without time zone ] 	  	date and time (no time zone)
        timestamp [ (p) ] with time zone 	timestamptz 	date and time, including time zone
        ## tsquery 	  	text search query
        ## tsvector 	  	text search document
        ## txid_snapshot 	  	user-level transaction ID snapshot
        ## uuid 	  	universally unique identifier
        ## xml 	  	XML data
         */

        // Order of converters and types gives you the order in which they will
        // be guessed if no type is specified, go from the more complex to the
        // lesser to ensure there is no data loss in such case.
        return (new ConverterMap())
            ->register('timestampz', new TimestampConverter(), ['timestamp', 'datetime'])
            ->register('date', new TimestampConverter())
            ->register('timez', new TimestampConverter(), ['time'])
            ->register('varchar', $default, ['character', 'char'])
            ->register('bytea', $default, ['blob']) // @todo
            ->register('boolean', new BooleanConverter(), ['bool'])
            ->register('bigint', new IntegerConverter(), ['int8'])
            ->register('bigserial', new IntegerConverter(), ['serial8'])
            ->register('integer', new IntegerConverter(), ['int', 'int4'])
            ->register('serial', new IntegerConverter(), ['serial4'])
            ->register('smallint', new IntegerConverter(), ['int2', 'smallserial', 'serial2'])
            ->register('double', new DecimalConverter(), ['float8'])
            ->register('numeric', new DecimalConverter(), ['decimal'])
            ->register('real', new DecimalConverter(), ['float4'])
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
    final protected function createConnection($encoding = 'utf8')
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

        return new EventEmitterConnectionProxy(new Session($connection), $this->getEventDispatcher());
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
