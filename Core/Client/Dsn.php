<?php

namespace Goat\Core\Client;

/**
 * Allow the following two reprensentations:
 *
 *   - scheme://example.com:3306/database
 *     where scheme can be either 'tcp' or 'mysql', hostname and port are optional
 *
 *   - unix:///path/to/socket:database
 *     where everything is mandatory
 */
class Dsn
{
    const DEFAULT_SCHEME = 'tcp';
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 3306;
    const DEFAULT_CHARSET = 'utf8';

    private $scheme = self::DEFAULT_SCHEME;
    private $host = self::DEFAULT_HOST;
    private $port = self::DEFAULT_PORT;
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

        if (!preg_match('!^
            ([\w]+)\://                 # Scheme
            ([^\:/]+)(\:(\w+)|)         # Hostname:port
            /(\w+)                      # Database
            $!x', $string, $matches)
        ) {
            if (!preg_match('!^
                (unix)://               # Scheme
                ([^\:]+)                # /path/to/socket
                \:(\w+)                 # Database
                $!x', $string, $matches)
            ) {
                throw new \InvalidArgumentException(sprintf("%s: invalid dsn", $string));
            }
        }

        if ('tcp' !== $matches[1] && 'mysql' !== $matches[1] && 'unix' !== $matches[1]) {
            throw new \InvalidArgumentException(sprintf("%s: only supports 'tcp', 'mysql' or 'unix' scheme, '%s' given", $string, $matches[1]));
        }

        if ('unix' === $matches[1]) {
            $this->scheme = 'unix';
            $this->host = $matches[2];
            $this->database = $matches[3];
            $this->port = null;
        } else {

            if ('mysql' === $matches[1]) {
                $this->scheme = 'tcp';
            } else {
                $this->scheme = $matches[1];
            }

            if (!empty($matches[2])) {
                $this->host = $matches[2];
            }

            if (!empty($matches[4])) {
                if ($matches[4] != (int)$matches[4]) {
                    throw new \InvalidArgumentException(sprintf("%s: port must be integer, '%s' given", $string, $matches[4]));
                }
                $this->port = (int)$matches[4];
            }

            if (!empty($matches[5])) {
                $this->database = $matches[5];
            }
        }

        if (empty($this->database)) {
            throw new \InvalidArgumentException(sprintf("%s: database name is mandatory", $string, $matches[1]));
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

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getHost()
    {
        if ($this->isUnixSocket()) {
            throw new \LogicException("You cannot get the host when the scheme is unix://");
        }

        return $this->host;
    }

    public function getPort()
    {
        if ($this->isUnixSocket()) {
            throw new \LogicException("You cannot get the port when the scheme is unix://");
        }

        return $this->port;
    }

    public function getSocketPath()
    {
        if (!$this->isUnixSocket()) {
            throw new \LogicException("You cannot get the socket path when the scheme is not unix://");
        }

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
            return 'unix://' . $this->host . ':' . $this->database;
        } else {
            return $this->scheme . '://' . $this->host . ':' . $this->port . '/' . $this->database;
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
            $dsn = 'mysql:unix_socket=' . $this->host;
        } else {
            $dsn = 'mysql:host=' . $this->host;
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
            return 'unix://' . $this->host;
        } else {
            return $this->scheme . '://' . $this->host . ':' . $this->port;
        }
    }
}
