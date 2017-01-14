<?php

namespace Goat\Driver\PgSQL;

use Goat\Core\Client\AbstractConnection;
use Goat\Core\Client\Dsn;
use Goat\Core\Client\EmptyResultIterator;
use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Error\ConfigurationError;
use Goat\Core\Error\DriverError;
use Goat\Core\Error\GoatError;
use Goat\Core\Error\NotImplementedError;
use Goat\Core\Error\QueryError;
use Goat\Core\Query\Query;
use Goat\Core\Query\SqlFormatterInterface;
use Goat\Core\Transaction\Transaction;

class PgSQLConnection extends AbstractConnection
{
    use PgSQLErrorTrait;

    /**
     * @var resource
     */
    private $conn;

    /**
     * @var string[]
     */
    private $prepared = [];

    /**
     * {@inheritdoc}
     */
    public function supportsReturning() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->conn) {
            pg_close($this->conn);
        }
    }

    /**
     * Connect to database
     *
     * @return \PDO
     */
    protected function connect()
    {
        $this->conn = pg_connect($this->dsn->formatPgSQL(), PGSQL_CONNECT_FORCE_NEW);

        if (false === $this->conn) {
            throw new ConfigurationError(sprintf("Error connecting to the database with parameters '%s'.", $this->dsn->formatFull()));
        }

        if ($this->configuration) {
            $this->sendConfiguration($this->configuration);
        }
    }

    /**
     * Get connection resource
     *
     * @return resource
     */
    protected function getConn()
    {
        if (!$this->conn) {
            $this->connect();
        }

        return $this->conn;
    }

    /**
     * {@inheritdoc}
     */
    protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ) : Transaction
    {
        $ret = new PgSQLTransaction($isolationLevel);
        $ret->setConnection($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral(string $string) : string
    {
        return pg_escape_literal($this->getConn(), $string);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word) : string
    {
        return pg_escape_bytea($this->getConn(), $word);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string) : string
    {
        return pg_escape_identifier($this->getConn(), $string);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateResultIterator(...$constructorArgs) : ResultIteratorInterface
    {
        return new PgSQLResultIterator(...$constructorArgs);
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

        $rawSQL = null;
        $conn = $this->getConn();

        try {
            list($rawSQL, $parameters) = $this->getProperSql($query, $parameters);
            $resource = pg_query_params($conn, $rawSQL, $parameters);

            if (false === $resource) {
                // Better sql error handlings
                throw new DriverError($rawSQL, $parameters);
            }

            $ret = $this->createResultIterator($options, $resource);
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
        $rawSQL = null;
        $conn = $this->getConn();

        try {
            list($rawSQL, $parameters) = $this->getProperSql($query, $parameters);
            $resource = pg_query_params($conn, $rawSQL, $parameters);

            if (false === $resource) {
                // Better sql error handlings
                throw new DriverError($rawSQL, $parameters);
            }

            $rowCount = pg_affected_rows($resource);
            if (false === $rowCount) {
                $this->throwIfError(pg_result_status($resource));
            }

            return $rowCount;

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
    public function prepareQuery($query, string $identifier = null) : string
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
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        if (!isset($this->prepared[$identifier])) {
            throw new QueryError(sprintf("'%s': query was not prepared", $identifier));
        }

        return $this->query($this->prepared[$identifier], $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getSqlFormatter() : SqlFormatterInterface
    {
        return $this->formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding(string $encoding)
    {
        // https://www.postgresql.org/docs/9.3/static/multibyte.html#AEN34087
        // @todo investigate differences between versions

        throw new NotImplementedError();

        // @todo this cannot work
        $this
            ->getConn()
            ->query(
                sprintf(
                    "SET CLIENT_ENCODING TO %s",
                    $this->escapeLiteral($encoding)
                )
            )
        ;
    }

    /**
     * Send PDO configuration
     */
    protected function sendConfiguration(array $configuration)
    {
        $pdo = $this->getConn();

        foreach ($configuration as $key => $value) {
            $pdo->query(sprintf(
                "SET %s TO %s",
                $this->escapeIdentifier($key),
                $this->escapeLiteral($value)
            ));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function writeCast(string $placeholder, string $type) : string
    {
        // No surprises there, PostgreSQL is very straight-forward and just
        // uses the datatypes as it handles it. Very stable and robust.
        return sprintf("%s::%s", $placeholder, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPlaceholder(int $index) : string
    {
        return '$' . ($index + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTables($relationNames)
    {
        if (!$relationNames) {
            throw new QueryError("cannot not truncate no tables");
        }

        $this->perform(sprintf("truncate %s", $this->escapeIdentifierList($relationNames)));
    }
}
