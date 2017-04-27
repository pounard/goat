<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Converter\ConverterAwareTrait;
use Goat\Converter\ConverterMap;
use Goat\Error\DriverError;
use Goat\Error\GoatError;
use Goat\Error\NotImplementedError;
use Goat\Error\QueryError;
use Goat\Query\QueryFactoryInterface;
use Goat\Query\QueryFactoryRunnerTrait;
use Goat\Runner\EmptyResultIterator;
use Goat\Runner\ResultIteratorInterface;
use Goat\Runner\RunnerInterface;
use Goat\Runner\Transaction;

/**
 * Drupal 7 runnable: not a Driver: it doesn't need to handle the connection
 */
class Drupal7Runner implements RunnerInterface, QueryFactoryInterface
{
    use ConverterAwareTrait;
    use QueryFactoryRunnerTrait;

    private $connection;
    private $escaper;
    private $formatter;
    private $prepared = [];

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $connection
     *   Drupal 7 database connection
     */
    public function __construct(\DatabaseConnection $connection)
    {
        $this->connection = $connection;

        switch ($connection->driver()) {

            case 'mysql':
                $this->escaper = new PDOMySQLEscaper($connection);
                $this->formatter = new PDOMySQLFormatter($this->escaper);
                break;

            case 'pgsql':
                $this->escaper = new PDOPgSQLEscaper($connection);
                $this->formatter = new PDOPgSQLFormatter($this->escaper);
                break;

            default:
                throw new NotImplementedError(sprintf("database '%s' target is not supported", $connection->driver()));
        }

        $this->setConverter(new ConverterMap());
    }

    /**
     * Creates a new transaction
     *
     * If a transaction is pending, continue the same transaction by adding a
     * new savepoint that will be transparently rollbacked in case of failure
     * in between.
     *
     * @param int $isolationLevel
     *   Default transaction isolation level, it is advised that you set it
     *   directly at this point, since some drivers don't allow isolation
     *   level changes while transaction is started
     * @param bool $allowPending = false
     *   If set to true, explicitely allow to fetch the currently pending
     *   transaction, else errors will be raised
     *
     * @throws TransactionError
     *   If you asked a new transaction while another one is opened, or if the
     *   transaction fails starting
     *
     * @return Transaction
     */
    public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false) : Transaction
    {
        throw new NotImplementedError();
    }

    /**
     * Is there a pending transaction
     *
     * @return bool
     */
    public function isTransactionPending() : bool
    {
        throw new NotImplementedError();
    }

    /**
     * Create the result iterator instance
     *
     * @param $options = null
     *   Query options
     * @param ...$constructorArgs
     *   Driver specific parameters
     *
     * @return ResultIteratorInterface
     */
    final private function createResultIterator($options = null, ...$constructorArgs) : ResultIteratorInterface
    {
        $result = new DefaultResultIterator(...$constructorArgs);
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
    public function query($query, array $parameters = null, $options = null) : ResultIteratorInterface
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

        return $this->query($this->prepared[$identifier], $parameters, $options);
    }
}
