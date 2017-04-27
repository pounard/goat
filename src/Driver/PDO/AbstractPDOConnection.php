<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Driver\AbstractDriver;
use Goat\Driver\Dsn;
use Goat\Error\ConfigurationError;
use Goat\Error\DriverError;
use Goat\Error\GoatError;
use Goat\Error\QueryError;
use Goat\Query\Query;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\EmptyResultIterator;
use Goat\Runner\ResultIteratorInterface;

abstract class AbstractPDOConnection extends AbstractDriver
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
    protected function doCreateResultIterator(...$constructorArgs) : ResultIteratorInterface
    {
        return new DefaultResultIterator(...$constructorArgs);
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

            $statement = $this->getPdo()->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);

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
            $prepared = $this->formatter->prepare($query, $parameters);
            $rawSQL   = $prepared->getQuery();
            $args     = $prepared->getParameters();

            // We still use PDO prepare emulation, it's better for security
            $statement = $this->getPdo()->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);

            // echo $rawSQL, "\n\n";

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

    /**
     * {@inheritdoc}
     */
    public function getFormatter() : FormatterInterface
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
