<?php

namespace Momm\Foundation\Session;

use PommProject\Foundation\Exception\ConnectionException;
use PommProject\Foundation\ParameterHolder;
use PommProject\Foundation\Session\ConnectionConfigurator as PommConnectionConfigurator;

class ConnectionConfigurator extends PommConnectionConfigurator
{
    /**
     * __construct
     *
     * Initialize configuration.
     *
     * @access public
     * @param  array $dsn
     */
    public function __construct($dsn)
    {
        $this->configuration = new ParameterHolder(
            [
                'dsn' => $dsn,
                'configuration' => $this->getDefaultConfiguration(),
            ]
        );
        $this->parseDsn();
    }

    /**
     * {@inheritdoc}
     */
    private function parseDsn()
    {
        $dsn = $this->configuration->mustHave('dsn')->getParameter('dsn');
        if (!preg_match(
            '#([a-z]+)://([^:@]+)(?::([^@]*))?(?:@([\w\.-]+|!/.+[^/]!)(?::(\w+))?)?/(.+)#',
            $dsn,
            $matches
        )) {
            throw new ConnectionException(sprintf('Could not parse DSN "%s".', $dsn));
        }

        if ($matches[1] == null || $matches[1] !== 'mysql') {
            throw new ConnectionException(
                sprintf(
                    "bad protocol information '%s' in dsn '%s'. Momm does only support 'mysql'.",
                    $matches[1],
                    $dsn
                )
            );
        }

        $adapter = $matches[1];

        if ($matches[2] === null) {
            throw new ConnectionException(
                sprintf(
                    "No user information in dsn '%s'.",
                    $dsn
                )
            );
        }

        $user = $matches[2];
        $pass = $matches[3];

        if (preg_match('/!(.*)!/', $matches[4], $host_matches)) {
            $host = $host_matches[1];
        } else {
            $host = $matches[4];
        }

        $port = $matches[5];

        if ($matches[6] === null) {
            throw new ConnectionException(
                sprintf(
                    "No database name in dsn '%s'.",
                    $dsn
                )
            );
        }

        $database = $matches[6];
        $this->configuration
            ->setParameter('adapter', $adapter)
            ->setParameter('user', $user)
            ->setParameter('pass', $pass)
            ->setParameter('host', $host)
            ->setParameter('port', $port)
            ->setParameter('database', $database)
            ->mustHave('user')
            ->mustHave('database')
            ;

        return $this;
    }

    /**
     * getConnectionString
     *
     * Return the connection string.
     *
     * @access public
     * @return string
     */
    public function getConnectionString()
    {
        $this->parseDsn();
        $connect_parameters = [
            sprintf(
                "user=%s dbname=%s",
                $this->configuration['user'],
                $this->configuration['database']
            )
        ];

        if ($this->configuration['host'] !== '') {
            $connect_parameters[] = sprintf('host=%s', $this->configuration['host']);
        }

        if ($this->configuration['port'] !== '') {
            $connect_parameters[] = sprintf('port=%s', $this->configuration['port']);
        }

        if ($this->configuration['pass'] !== '') {
            $connect_parameters[] = sprintf('password=%s', addslashes($this->configuration['pass']));
        }

        return join(' ', $connect_parameters);
    }

    public function getPdoDsn()
    {
        $map = [
            'database'  => 'dbname',
            'charset'   => 'charset',
            'port'      => 'port',
            'host'      => 'host',
        ];

        $parts = [];

        foreach ($map as $key => $name) {
            if ($value = $this->configuration->getParameter($name)) {
                $parts[] = $key . '=' . $value;
            }
        }

        return 'mysql:' . implode(';', $parts);
    }

    public function getUsername()
    {
        return $this->configuration->getParameter('user');
    }

    public function getPassword()
    {
        return $this->configuration->getParameter('pass');
    }
}
