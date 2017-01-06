<?php

namespace Goat\Driver\PDO;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Client\ConnectionTrait;
use Goat\Core\Client\Dsn;
use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Error\ConfigurationError;
use Goat\Core\Error\DriverError;
use Goat\Core\Error\QueryError;
use Goat\Core\Query\InsertQueryQuery;
use Goat\Core\Query\InsertValuesQuery;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\SqlFormatter;
use Goat\Core\Query\SqlFormatterInterface;

abstract class AbstractConnection implements ConnectionInterface
{
    use ConnectionTrait;

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
        return new DefaultResultIterator($statement, $enableConverters);
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql, array $parameters = [], $enableConverters = true)
    {
        try {
            list($sql, $parameters) = $this->rewriteQueryAndParameters($sql, $parameters);

            $statement = $this
                ->getPdo()
                ->prepare(
                    $sql,
                    [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
                )
            ;

            $statement->execute($parameters);

            $ret = $this->createResultIterator($statement, $enableConverters);
            $ret->setConverter($this->converter);

            return $ret;

        } catch (\PDOException $e) {
            throw new DriverError($sql, $parameters, $e);
        } catch (\Exception $e) {
            throw new DriverError($sql, $parameters, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($sql, $identifier = null)
    {
        if (null === $identifier) {
            $identifier = md5($sql);
        }

        // Default behaviour, because databases such as MySQL don't really
        // prepare SQL statements, is to emulate it by keeping a copy of the
        // SQL query in memory and giving to the user a computed identifier.
        $this->prepared[$identifier] = $sql;

        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery($identifier, array $parameters = [], $enableConverters = true)
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
