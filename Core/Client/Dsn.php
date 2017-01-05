<?php

namespace Goat\Core\Client;

/**
 * Allow the following two reprensentations:
 *
 *   - [tcp://]DBTYPE://[HOSTNAME[:PORT]]/DATABASE
 *
 *     examples:
 *       - pgsql://1.2.3.4:6578/my_database
 *       - pgsql:///my_database
 *       - tcp://mysql://somehost.example.net/my_database
 *
 *   - [unix://]DBTYPE://PATH:DATABASE
 *
 *     examples:
 *       - pgsql:///path/to/pg.sock:my_database
 *       - unix://mysql:///path/to/my.sock:my_database
 */
final class Dsn
{
    const SCHEME_TCP = 'tcp';
    const SCHEME_UNIX = 'unix';

    const REGEX_TCP = '@^(tcp\://|)([\w]+)\://(([^/\:]+)(\:(\d+)|)|)/([^\.]+)$@';
    const REGEX_UNIX = '@^(unix\://|)([\w]+)\://(/[^\:]+)\:(.+)$@';

    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT_MYSQL = 3306;
    const DEFAULT_PORT_PGSQL = 5432;
    const DEFAULT_CHARSET = 'utf8';

    private $driver;
    private $scheme;
    private $host;
    private $port;
    private $charset = self::DEFAULT_CHARSET;
    private $database;
    private $username;
    private $password;

    /**
     * Default constructor
     *
     * @param string $string
     * @param string $username
     * @param string $password
     * @param string $charset
     *
     * @throws \InvalidArgumentException
     *   On invalid dsn given
     */
    public function __construct($string, $username = null, $password = null, $charset = self::DEFAULT_CHARSET)
    {
        $matches = [];

        if (preg_match(self::REGEX_TCP, $string, $matches)) {

            $this->scheme   = self::SCHEME_TCP;
            $this->driver   = $matches[2];
            $this->host     = $matches[4];
            $this->port     = (int)$matches[6];
            $this->database = $matches[7];

            if (empty($this->port)) {
                switch ($this->driver) {
                    case 'mysql':
                        $this->port = self::DEFAULT_PORT_MYSQL;
                        break;
                      case 'pgsql':
                        $this->port = self::DEFAULT_PORT_PGSQL;
                        break;
                }
            } else {
                if (!is_int($this->port)) {
                    throw new \InvalidArgumentException(sprintf("%s: port must be integer, '%s' given", $string, $this->port));
                }
            }

            if (empty($this->host)) {
                $this->host = self::DEFAULT_HOST;
            }

        } else if (preg_match(self::REGEX_UNIX, $string, $matches)) {

            $this->scheme   = self::SCHEME_UNIX;
            $this->driver   = $matches[2];
            $this->host     = $matches[3];
            $this->database = $matches[4];

        } else {
            throw new \InvalidArgumentException(sprintf("%s: invalid dsn", $string));
        }

        if ('pgsql' !== $this->driver && 'mysql' !== $this->driver) {
            throw new \InvalidArgumentException(sprintf("%s: only supports 'pgsql', 'mysql' drivers, '%s' given", $string, $this->driver));
        }

        if (empty($this->database)) {
            throw new \InvalidArgumentException(sprintf("%s: database name is mandatory", $string));
        }

        $this->username = $username;
        $this->password = $password;
        if ($charset) {
            $this->charset = $charset;
        }
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getSocketPath()
    {
        return $this->host;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Is the current dsn a unix socket path
     *
     * @return boolean
     */
    public function isUnixSocket()
    {
        return 'unix' === $this->scheme;
    }

    /**
     * Retrieve the original string with all information
     *
     * @return string
     */
    public function formatFull()
    {
        if ($this->isUnixSocket()) {
            return $this->formatWithoutDatabase() . ':' . $this->database;
        } else {
            return $this->formatWithoutDatabase() . '/' . $this->database;
        }
    }

    /**
     * phpredis drops the scheme and database information
     *
     * @return string
     */
    public function formatPdo()
    {
        $map = [
            'port'    => $this->port,
            'dbname'  => $this->database,
            // We need to default to something
            'charset' => $this->charset,
        ];

        if ($this->isUnixSocket()) {
            $dsn = $this->driver . ':unix_socket=' . $this->host;
        } else {
            $dsn = $this->driver . ':host=' . $this->host;
        }

        foreach ($map as $key => $value) {
            if ($value) {
                $dsn .= ';' . $key . '=' . $value;
            }
        }

        return $dsn;
    }

    /**
     * Format without the database
     *
     * @return string
     */
    public function formatWithoutDatabase()
    {
        if ($this->isUnixSocket()) {
            return 'unix://' . $this->driver . '://' . $this->host;
        } else {
            // Omit 'tcp://' this is the most usual case
            return $this->driver . '://' . $this->host . ':' . $this->port;
        }
    }
}
