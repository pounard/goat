<?php

namespace Momm\Foundation\Session;

use PommProject\Foundation\Exception\ConnectionException;
use PommProject\Foundation\Session\Connection as PommConnection;

class Connection extends PommConnection
{
    /**
     * @var \PDO
     */
    private $pdo;
    private $is_closed = false;

    /**
     * __construct
     *
     * Constructor. Test if the given DSN is valid.
     *
     * @access public
     * @param  string $dsn
     * @param  array $configuration
     * @throws ConnectionException if pgsql extension is missing
     */
    public function __construct($dsn, array $configuration = [])
    {
        // We need to override this method to avoid calls to pg_* methods.
        $this->configurator = new ConnectionConfigurator($dsn);
        $this->configurator->addConfiguration($configuration);
    }

    /**
     * {@inheritdoc}
     */
    protected function hasHandler()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->pdo) {
            unset($this->pdo);
            $this->is_closed = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandler()
    {
        throw new \Exception("MySQL connector does not use a resource handler");
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionStatus()
    {
        if (!$this->pdo) {
            if ($this->is_closed) {
                return static::CONNECTION_STATUS_CLOSED;
            } else {
                return static::CONNECTION_STATUS_NONE;
            }
        }

        return static::CONNECTION_STATUS_GOOD;

        // @todo can PDO handle this ?
        // return static::CONNECTION_STATUS_BAD;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionStatus()
    {
        // @todo can we do better?
        throw new \Exception("PDO does not seem to be able to tell that, am I wrong?");
    }

    /**
     * {@inheritdoc}
     */
    protected function launch()
    {
        $options = [];

        try {
            $this->pdo = new \PDO(
                $this->configurator->getPdoDsn(),
                $this->configurator->getUsername(),
                $this->configurator->getPassword(),
                $options
            );

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        } catch (\PDOException $e) {
            throw new ConnectionException(
                sprintf(
                    "Error connecting to the database with parameters '%s'.",
                    preg_replace('/password=[^ ]+/', 'password=xxxx', $this->configurator->getConnectionString())
                ),
                null,
                $e
            );
        }

        $this->sendConfiguration();

        return $this;
    }

    protected function getPdo()
    {
        if (!$this->pdo) {
            $this->launch();
        }

        return $this->pdo;
    }

    /**
     * {@inheritdoc}
     */
    protected function sendConfiguration()
    {
        $sql = [];

        foreach ($this->configurator->getConfiguration() as $setting => $value) {
            $sql[] = sprintf(
                "set %s = %s",
                $this->getPdo()->quote($setting, \PDO::PARAM_STR),
                $this->getPdo()->quote($value, \PDO::PARAM_STR)
            );
        }

        if ($sql) {
            foreach ($sql as $queryString) {
                $this->getPdo()->query($queryString);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Because the MySQL connector might or might not support asynchronous
     * queries, we will just send the query synchronously and return the
     * result handler.
     */
    public function executeAnonymousQuery($sql)
    {
        return new ResultHandler($this->getPdo()->query($sql));
    }

    /**
     * {@inheritdoc}
     */
    protected function getQueryResult($sql = null)
    {
        throw new \Exception("MySQL connector does not support ASYNC queries yet");
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier($string)
    {
        return '`' . str_replace('`', '\\`', $string) . '`';
    }

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
    public function escapeBytea($word)
    {
        return $this->getPdo()->quote($word, \PDO::PARAM_LOB);
    }

    /**
     * {@inheritdoc}
     *
     * If I remember correctly, PDO should return unescaped blobs
     */
    public function unescapeBytea($bytea)
    {
        return $bytea;
    }

    protected function convertStuffFromPgSyntaxToPdoSyntax($sql)
    {
        // compatiblity with pg_* functions
        // @todo restore parameter order
        $sql = preg_replace('/\$(\d*|\*)/', '?', $sql);

        // @todo lots to do here, like param and type conversion etc...

        // this will replace ::TYPE strings
        // @todo it's too wide and will also potentially truncate valid strings
        $sql = preg_replace('/\:\:([a-zA-Z0-9]+)/', '', $sql);

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function sendQueryWithParameters($query, array $parameters = [])
    {
        $query = $this->convertStuffFromPgSyntaxToPdoSyntax($query);

        $statement = $this->getPdo()->prepare($query);
        $statement->execute($parameters);

        return new ResultHandler($statement);
    }

    /**
     * {@inheritdoc}
     */
    public function sendPrepareQuery($identifier, $sql)
    {
        $sql = $this->convertStuffFromPgSyntaxToPdoSyntax($sql);
        $pdo = $this->getPdo();

        // PDO will emulate prepared queries, so we will directly hit MySQL
        // with its own syntax, not sure this will really avoid potential SQL
        // injection, but at the very least it will allow to avoid injection via
        // parameters
        $pdo
            ->query(sprintf(
                "prepare %s from %s",
                $this->escapeIdentifier($identifier),
                $pdo->quote($sql)
            ))
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function testQuery($query_return, $sql)
    {
        throw new \Exception("This makes no sense with PDO::MySQL driver");
    }

    /**
     * {@inheritdoc}
     */
    public function sendExecuteQuery($identifier, array $parameters = [], $sql = '')
    {
        $pdo = $this->getPdo();

        $name = 'a';
        $map = [];
        foreach ($parameters as $value) {
            $escapedName = $this->escapeIdentifier($name);
            $pdo->query(sprintf("set @%s = %s", $escapedName, $pdo->quote($value)));
            $map[] = '@' . $escapedName;
            ++$name;
        }

        $statement = $this->pdo->query(sprintf("execute %s using %s", $this->escapeIdentifier($identifier), join(', ', $map)));

        return new ResultHandler($statement);
    }

    /**
     * {@inheritdoc}
     */
    public function getClientEncoding()
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding($encoding)
    {
        $this->getPdo()->query("SET character_set_client = ?", [$encoding]);
    }

    /**
     * {@inheritdoc}
     */
    public function getNotification()
    {
        throw new \Exception("MySQL does not implement notifications");
    }
}
