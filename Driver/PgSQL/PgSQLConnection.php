<?php

declare(strict_types=1);

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

/**
 * ext_pgsql connection implementation
 */
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
    protected function getEscapeSequences() : array
    {
        return [
            '"',    // Identifier escape character
            '\'',   // String literal escape character
            '$$',   // String constant escape sequence
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchDatabaseInfo() : array
    {
        $conn = $this->getConn();
        $resource = @pg_query($conn, "select version();");

        if (false === $resource) {
            $this->connectionError($conn);
        }

        $row = @pg_fetch_array($resource);
        if (false === $row) {
            $this->resultError($resource);
        }

        // Example string to parse:
        //   PostgreSQL 9.2.9 on x86_64-unknown-linux-gnu, compiled by gcc (GCC) 4.4.7 20120313 (Red Hat 4.4.7-4), 64-bit
        $string = reset($row);
        $pieces = explode(', ', $string);
        $server = explode(' ', $pieces[0]);

        return [
            'name'    => $server[0],
            'version' => $server[1],
            'arch'    => $pieces[2],
            'os'      => $server[3],
            'build'   => $pieces[1],
        ];
    }

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
            @pg_close($this->conn);
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
        if ('' === $string) {
            return '';
        }

        $conn = $this->getConn();

        $escaped = @pg_escape_literal($conn, $string);
        if (false === $escaped) {
            $this->connectionError($conn);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word) : string
    {
        if ('' === $word) {
            return '';
        }

        $conn = $this->getConn();

        $escaped = @pg_escape_bytea($conn, $word);
        if (false === $escaped) {
            $this->connectionError($conn);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string) : string
    {
        if ('' === $string) {
            return '';
        }

        $conn = $this->getConn();

        $escaped = @pg_escape_identifier($conn, $string);
        if (false === $escaped) {
            $this->connectionError($conn);
        }

        return $escaped;
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

        $rawSQL = '';
        $conn = $this->getConn();

        try {
            list($rawSQL, $parameters) = $this->getProperSql($query, $parameters);
            $resource = @pg_query_params($conn, $rawSQL, $parameters);

            if (false === $resource) {
                $this->connectionError($conn);
            }

            $ret = $this->createResultIterator($options, $resource);
            $ret->setConverter($this->converter);

            return $ret;

        } catch (GoatError $e) {
            throw $e;
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
        $conn = $this->getConn();

        try {
            list($rawSQL, $parameters) = $this->getProperSql($query, $parameters);
            $resource = @pg_query_params($conn, $rawSQL, $parameters);

            if (false === $resource) {
                $this->connectionError($conn);
            }

            $rowCount = pg_affected_rows($resource);
            if (false === $rowCount) {
                $this->resultError($resource);
            }

            return $rowCount;

        } catch (GoatError $e) {
            throw $e;
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
