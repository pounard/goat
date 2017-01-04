<?php

namespace Goat\Driver\PDO;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Client\ConnectionTrait;
use Goat\Core\Client\Dsn;

class PDOConnection implements ConnectionInterface
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
     * Constructor
     *
     * @param string $dsn
     * @param string[] $configuration
     */
    public function __construct($dsn, $username = null, $password = null, array $configuration = [])
    {
        $this->dsn = new Dsn($dsn, $username, $password);
        $this->configuration = $configuration;
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
            throw new \RuntimeException(sprintf("Error connecting to the database with parameters '%s'.", $this->dsn->formatFull()), null, $e);
        }

        $this->sendConfiguration();
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
     */
    protected function sendConfiguration()
    {
        $sql = [];

        foreach ($this->configuration as $setting => $value) {
            $sql[] = sprintf(
                "set %s = %s",
                $this->escapeIdentifier($setting, \PDO::PARAM_STR),
                $this->escapeLiteral($value, \PDO::PARAM_STR)
            );
        }

        if ($sql) {
            foreach ($sql as $queryString) {
                $this->pdo->query($queryString);
            }
        }

        return $this;
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
    public function escapeBlob($word)
    {
        return $this->getPdo()->quote($word, \PDO::PARAM_LOB);
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql, array $parameters = [], $enableConverters = true)
    {
        list($sql, $parameters) = $this->rewriteQueryAndParameters($sql, $parameters);

        $statement = $this
            ->getPdo()
            ->prepare(
                $sql,
                [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            )
        ;

        $statement->execute($parameters);

        $ret = new PDOResultIterator($statement, $enableConverters);
        $ret->setConverter($this->converter);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($sql, $identifier = null)
    {
        if (null === $identifier) {
            $identifier = md5($sql);
        }

        // MySQL is so stupid when it comes to prepared statements that I would
        // prefer to just store the SQL and run it later...
        $this->prepared[$identifier] = $sql;

        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery($identifier, array $parameters = [])
    {
        if (!isset($this->prepared[$identifier])) {
            throw new \LogicException(sprintf("'%s': query was not prepared", $identifier));
        }

        return $this->query($this->prepared[$identifier], $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding($encoding)
    {
        $this->getPdo()->query("SET character_set_client = ?", [$encoding]);
    }
}
