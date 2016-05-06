<?php

namespace Momm\Foundation\Session;

use Momm\Foundation\Converter\MyConverterInterface;

use PommProject\Foundation\Exception\ConnectionException;
use PommProject\Foundation\Session\Connection as PommConnection;
use PommProject\Foundation\Session\Session;

class Connection extends PommConnection
{
    /**
     * @var \PDO
     */
    private $pdo;
    private $is_closed = false;
    private $prepared = [];

    private $session;

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

    public function setSession(Session $session)
    {
        $this->session = $session;
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
        return $this->sendQueryWithParameters($sql, []);
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

    /**
     * @see \PommProject\Foundation\QueryManager\QueryParameterParserTrait::getParametersType()
     */
    protected function getParametersType($string)
    {
        $matches = [];
        preg_match_all('/\$(\*|\d+)(?:::([\w\."]+(?:\[\])?))?/', $string, $matches);

        $ret = [];
        foreach ($matches[0] as $i => $identifier) {
            $ret[$identifier] = str_replace('"', '', $matches[2][$i]);
        }
        return $ret;
    }

    protected function convertStuffFromPgSyntaxToPdoSyntax($sql)
    {
        $typeMap = $this->getParametersType($sql);

        $replacements = [];

        foreach ($typeMap as $original => $type) {
            // compatiblity with pg_* functions
            // @todo restore parameter order
            $replacement = '?';
            if ($type !== '') {
                $converterClient = $this->session->getClientUsingPooler('converter', $type);
                /** @var $converterClient \PommProject\Foundation\Converter\ConverterClient */
                if ($converterClient) {
                    $converter = $converterClient->getConverter();
                    if ($converter instanceof MyConverterInterface && $converter->needsCast()) {
                        // type conversion so that PDOStatement::getColumnMeta()
                        // will return the right type to us
                        $replacement = sprintf("cast(? as %s)", $converter->castAs($type));
                    }
                }
            }

            $replacements[$original] = $replacement;
        }

        return strtr($sql, $replacements);
    }

    /**
     * {@inheritdoc}
     */
    public function sendQueryWithParameters($query, array $parameters = [])
    {
        $query = $this->convertStuffFromPgSyntaxToPdoSyntax($query);

        $statement = $this
            ->getPdo()
            ->prepare(
                $query,
                [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]
            )
        ;

        $statement->execute($parameters);

        return new ResultHandler($statement);
    }

    /**
     * {@inheritdoc}
     */
    public function sendPrepareQuery($identifier, $sql)
    {
        $this->prepared[$identifier] = $sql;

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
        if (!isset($this->prepared[$identifier])) {
            throw new \LogicException(sprintf("'%s': query was not prepared", $identifier));
        }

        return $this->sendQueryWithParameters($this->prepared[$identifier], $parameters);
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
