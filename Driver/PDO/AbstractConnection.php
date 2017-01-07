<?php

namespace Goat\Driver\PDO;

use Goat\Core\Client\AbstractConnection as BaseConnection;
use Goat\Core\Client\Dsn;
use Goat\Core\Client\EmptyResultIterator;
use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Converter\ConverterMap;
use Goat\Core\Error\ConfigurationError;
use Goat\Core\Error\DriverError;
use Goat\Core\Error\GoatError;
use Goat\Core\Error\QueryError;
use Goat\Core\Query\InsertQueryQuery;
use Goat\Core\Query\InsertValuesQuery;
use Goat\Core\Query\Query;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\SqlFormatter;
use Goat\Core\Query\SqlFormatterInterface;

abstract class AbstractConnection extends BaseConnection
{
    /**
     * @var Dsn
     */
    private $dsn;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var string[]
     */
    private $configuration = [];

    /**
     * @var string[]
     */
    private $prepared = [];

    /**
     * @var SqlFormatterInterface
     */
    private $formatter;

    /**
     * Constructor
     *
     * @param string|Dsn $dsn
     * @param string[] $configuration
     */
    public function __construct($dsn, $username = null, $password = null, array $configuration = [])
    {
        if ($dsn instanceof Dsn) {
            $this->dsn = $dsn;
        } else {
            $this->dsn = new Dsn($dsn, $username, $password);
        }

        $this->configuration = $configuration;
        $this->formatter = new SqlFormatter($this);

        // Register an empty instance for the converter, in case.
        $this->converter = new ConverterMap();
    }

    /**
     * Connect to database
     *
     * @return \PDO
     */
    protected function connect()
    {
        $options = [];

        try {
            $this->pdo = new \PDO(
                $this->dsn->formatPdo(),
                $this->dsn->getUsername(),
                $this->dsn->getPassword(),
                $options
            );

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        } catch (\PDOException $e) {
            // @todo do better
            throw new ConfigurationError(sprintf("Error connecting to the database with parameters '%s'.", $this->dsn->formatFull()), null, $e);
        }

        if ($this->configuration) {
            $this->sendConfiguration($this->configuration);
        }
    }

    protected function getPdo()
    {
        if (!$this->pdo) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Send PDO configuration
     *
     * @param string[] $configuration
     *   Keys are variable names, values are scalar values
     */
    abstract protected function sendConfiguration(array $configuration);

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral($string)
    {
        return $this->getPdo()->quote($string, \PDO::PARAM_STR);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob($word)
    {
        return $this->getPdo()->quote($word, \PDO::PARAM_LOB);
    }

    /**
     * Create the result iterator instance
     *
     * @param \PDOStatement $statement
     * @param string $enableConverters
     *
     * @return ResultIteratorInterface
     */
    protected function createResultIterator(\PDOStatement $statement, $enableConverters = true)
    {
        return new DefaultResultIterator($statement, $this->converter, $enableConverters);
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, $parameters = null, $enableConverters = true)
    {
        if ($query instanceof Query) {
            if (!$query->willReturnRows()) {
                $affectedRowCount = $this->perform($query, $parameters);

                return new EmptyResultIterator($affectedRowCount);
            }
        }

        $rawSQL = null;

        try {
            list($rawSQL, $parameters) = $this->getProperSql($query, $parameters);

            $statement = $this->getPdo()->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($parameters);

            $ret = $this->createResultIterator($statement, $enableConverters);
            $ret->setConverter($this->converter);

            // echo $rawSQL, "\n\n";

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
    public function perform($query, $parameters = null, $enableConverters = true)
    {
        $rawSQL = null;

        try {
            list($rawSQL, $parameters) = $this->getProperSql($query, $parameters);

            // We still use PDO prepare emulation, it's better for security
            $statement = $this->getPdo()->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($parameters);

            // echo $rawSQL, "\n\n";

            return $statement->rowCount();

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
    public function prepareQuery($query, $identifier = null)
    {
        list($rawSQL) = $this->getProperSql($query);

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
    public function executePreparedQuery($identifier, $parameters = null, $enableConverters = true)
    {
        if (!isset($this->prepared[$identifier])) {
            throw new QueryError(sprintf("'%s': query was not prepared", $identifier));
        }

        return $this->query($this->prepared[$identifier], $parameters, $enableConverters);
    }

    /**
     * {@inheritdoc}
     */
    public function select($relation, $alias = null)
    {
        $select = new SelectQuery($relation, $alias);
        $select->setConnection($this);

        return $select;
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery($relation)
    {
        $insert = new InsertQueryQuery($relation);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    public function insertValues($relation)
    {
        $insert = new InsertValuesQuery($relation);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    public function getSqlFormatter()
    {
        return $this->formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding($encoding)
    {
        // SQL standard SET NAMES command.
        $this->getPdo()->query("SET NAMES ?", [$encoding]);
    }
}
