<?php

declare(strict_types=1);

namespace Goat\Driver\Drupal7;

use Goat\Converter\ConverterAwareTrait;
use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Driver\DriverConverter;
use Goat\Driver\MySQL\MySQLTransaction;
use Goat\Driver\PDO\PDOMySQLEscaper;
use Goat\Driver\PDO\PDOPgSQLEscaper;
use Goat\Driver\PDO\PDOResultIterator;
use Goat\Driver\PDO\PgSQLTransaction;
use Goat\Driver\PgSQL\PgSQLConverter;
use Goat\Error\DriverError;
use Goat\Error\GoatError;
use Goat\Error\NotImplementedError;
use Goat\Error\QueryError;
use Goat\Query\Query;
use Goat\Query\QueryFactoryRunnerTrait;
use Goat\Query\Driver\PDOMySQL5Formatter;
use Goat\Query\Driver\PDOPgSQLFormatter;
use Goat\Query\Writer\EscaperInterface;
use Goat\Runner\EmptyResultIterator;
use Goat\Runner\ResultIteratorInterface;
use Goat\Runner\RunnerInterface;
use Goat\Runner\RunnerTrait;
use Goat\Runner\Transaction;

/**
 * Drupal 7 runnable: not a Driver: it doesn't need to handle the connection
 */
class Drupal7Runner implements RunnerInterface
{
    use ConverterAwareTrait;
    use QueryFactoryRunnerTrait;
    use RunnerTrait;

    private $debug = false;
    private $connection;
    private $escaper;
    private $formatter;
    private $prepared = [];
    private $supportsDefering = false;
    private $supportsReturning = false;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $connection
     *   Drupal 7 database connection
     */
    public function __construct(\DatabaseConnection $connection)
    {
        $this->connection = $connection;

        switch ($this->getDatabaseType($connection)) {

            case 'mysql':
                $this->escaper = new PDOMySQLEscaper($connection);
                $this->formatter = new PDOMySQL5Formatter($this->escaper);
                break;

            case 'pgsql':
                $this->escaper = new PDOPgSQLEscaper($connection);
                $this->formatter = new PDOPgSQLFormatter($this->escaper);
                $this->supportsDefering = true;
                $this->supportsReturning = true;
                break;

            default:
                throw new NotImplementedError(sprintf("database '%s' target is not supported", $connection->driver()));
        }

        $this->setConverter(new DefaultConverter());
    }

    private function getDatabaseType(\DatabaseConnection $connection)
    {
        $drupalDriver = $connection->driver();

        if (false !== stripos($drupalDriver, 'mysql')) {
            return 'mysql';
        } else if (false !== stripos($drupalDriver, 'pg')) {
            return 'pgsql';
        } else if (false !== stripos($drupalDriver, 'sqlite')) {
            return 'sqlite';
        } else {
            return $drupalDriver;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter)
    {
        $converter = new DriverConverter($converter, $this->getEscaper());

        switch ($this->getDatabaseType($this->connection)) {

            case 'pgsql':
                $converter = new PgSQLConverter($converter);
                break;
        }

        $this->converter = $converter;
        $this->formatter->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug(bool $debug = true)
    {
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugEnabled() : bool
    {
        return $this->debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName() : string
    {
        return $this->connection->driver();
    }

    /**
     * {@inheritdoc}
     */
    public function getEscaper() : EscaperInterface
    {
        return $this->escaper;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning() : bool
    {
        return $this->supportsReturning;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints() : bool
    {
        return $this->supportsDefering;
    }

    /**
     * {@inheritdoc}
     */
    protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ) : Transaction
    {
        // Real question being: should we or should we not proxify with Drupal
        // transactions? It'd make the whole more stable and robust, but our
        // code much more complex to maintain; and Drupal transactions are meant
        // to auto-commit when going out of scope, it does not match *at all*
        // the way we do things ourselves.
        switch ($this->connection->driver()) {

            case 'mysql':
                return new MySQLTransaction($this, $isolationLevel);

            case 'pgsql':
                return new PgSQLTransaction($this, $isolationLevel);

            default:
                throw new NotImplementedError(sprintf("database '%s' target is not supported", $this->connection->driver()));
        }
    }

    /**
     * Create the result iterator instance
     *
     * @param $options
     *   Query options
     * @param mixed[] $constructorArgs
     *   Driver specific parameters
     *
     * @return ResultIteratorInterface
     */
    final private function createResultIterator($options = null, ...$constructorArgs) : ResultIteratorInterface
    {
        $result = new PDOResultIterator(...$constructorArgs);
        $result->setConverter($this->converter);

        if ($options) {
            if (is_string($options)) {
                $options = ['class' => $options];
            } else if (!is_array($options)) {
                throw new QueryError("options must be a valid class name or an array of options");
            }
        }

        if (isset($options['class'])) {
            // Class can be either an alias or a valid class name, the hydrator
            // will proceed with all runtime checks to ensure that.
            $result->setHydrator($this->hydratorMap->get($options['class']));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($query, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        if ($query instanceof Query) {
            if (!$query->willReturnRows()) {
                $affectedRowCount = $this->perform($query, $parameters, $options);

                return new EmptyResultIterator($affectedRowCount);
            }
        }

        $rawSQL = '';

        try {
            $prepared = $this->formatter->prepare($query, $parameters);
            $rawSQL   = $prepared->getQuery();
            $args     = $prepared->getParameters();

            $statement = $this->connection->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);

            $ret = $this->createResultIterator($options, $statement);
            $ret->setConverter($this->converter);

            return $ret;

        } catch (GoatError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new DriverError($rawSQL, $parameters, $e);
        } catch (\Exception $e) {
            throw new DriverError($rawSQL, $parameters, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, array $parameters = null, $options = null) : int
    {
        $rawSQL = '';

        try {
            $prepared = $this->formatter->prepare($query, $parameters);
            $rawSQL   = $prepared->getQuery();
            $args     = $prepared->getParameters();

            // We still use PDO prepare emulation, it's better for security
            $statement = $this->connection->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);

            return $statement->rowCount();

        } catch (GoatError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new DriverError($rawSQL, [], $e);
        } catch (\Exception $e) {
            throw new DriverError($rawSQL, [], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null) : string
    {
        $prepared = $this->formatter->prepare($query);
        $rawSQL   = $prepared->getQuery();

        if (null === $identifier) {
            $identifier = md5($rawSQL);
        }

        // Default behaviour, because databases such as MySQL don't really
        // prepare SQL statements, is to emulate it by keeping a copy of the
        // SQL query in memory and giving to the user a computed identifier.
        $this->prepared[$identifier] = $rawSQL;

        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        if (!isset($this->prepared[$identifier])) {
            throw new QueryError(sprintf("'%s': query was not prepared", $identifier));
        }

        return $this->execute($this->prepared[$identifier], $parameters, $options);
    }
}
