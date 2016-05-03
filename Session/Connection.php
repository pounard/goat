<?php

namespace Momm\Foundation\Session;

use PommProject\Foundation\Exception\ConnectionException;
use PommProject\Foundation\Exception\SqlException;
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
        return $this->getPdo()->quote($string, \PDO::PARAM_STR);
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

    /**
     * {@inheritdoc}
     */
    public function sendQueryWithParameters($query, array $parameters = [])
    {
        // compatiblity with pg_* functions
        $query = preg_replace('/\$\d*/', '?', $query);

        $statement = $this->getPdo()->prepare($query);
        $statement->execute($parameters);

        return new ResultHandler($statement);
    }

    /**
     * sendPrepareQuery
     *
     * Send a prepare query statement to the server.
     *
     * @access public
     * @param  string     $identifier
     * @param  string     $sql
     * @return Connection $this
     */
    public function sendPrepareQuery($identifier, $sql)
    {
        $this
            ->testQuery(
                pg_send_prepare($this->getHandler(), $identifier, $sql),
                sprintf("Could not send prepare statement «%s».", $sql)
            )
            ->getQueryResult(sprintf("PREPARE ===\n%s\n ===", $sql))
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
     * sendExecuteQuery
     *
     * Execute a prepared statement.
     * The optional SQL parameter is for debugging purposes only.
     *
     * @access public
     * @param  string        $identifier
     * @param  array         $parameters
     * @param  string        $sql
     * @return ResultHandler
     */
    public function sendExecuteQuery($identifier, array $parameters = [], $sql = '')
    {
        $ret = pg_send_execute($this->getHandler(), $identifier, $parameters);

        return $this
            ->testQuery($ret, sprintf("Prepared query '%s'.", $identifier))
            ->getQueryResult(sprintf("EXECUTE ===\n%s\n ===\nparameters = {%s}", $sql, join(', ', $parameters)))
            ;
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
