<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Core\Client\AbstractConnection;
use Goat\Core\Client\Dsn;
use Goat\Core\Client\EmptyResultIterator;
use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Error\ConfigurationError;
use Goat\Core\Error\DriverError;
use Goat\Core\Error\GoatError;
use Goat\Core\Error\QueryError;
use Goat\Core\Query\Query;
use Goat\Core\Query\SqlFormatterInterface;

abstract class AbstractPDOConnection extends AbstractConnection
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var string[]
     */
    private $prepared = [];

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->pdo) {
            $this->pdo = null;
        }
    }

    /**
     * Connect to database
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

    /**
     * Get PDO instance, connect if not connected
     *
     * @return \PDO
     */
    protected function getPdo() : \PDO
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
    public function escapeLiteral(string $string) : string
    {
        return $this->getPdo()->quote($string, \PDO::PARAM_STR);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word) : string
    {
        return $this->getPdo()->quote($word, \PDO::PARAM_LOB);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateResultIterator(...$constructorArgs) : ResultIteratorInterface
    {
        return new DefaultResultIterator(...$constructorArgs);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPlaceholder(int $index) : string
    {
        return '?';
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
            list($rawSQL, $parameters) = $this->getProperSql($query, $parameters);

            $statement = $this->getPdo()->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($parameters);

            $ret = $this->createResultIterator($options, $statement);
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
    public function perform($query, array $parameters = null, $options = null) : int
    {
        $rawSQL = '';

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
        // SQL standard SET NAMES command.
        $this->getPdo()->query("SET NAMES ?", [$encoding]);
    }
}
