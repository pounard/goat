<?php

namespace Momm\Foundation\Session;

use PommProject\Foundation\Client\ClientHolder;
use PommProject\Foundation\Session\Connection as PommConnection;
use PommProject\Foundation\Session\SessionBuilder as PommSessionBuilder;

class SessionBuilder extends PommSessionBuilder
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfiguration()
    {
        return ["connection:configuration" => []];
    }

    /**
     * {@inheritdoc}
     */
    protected function createSession(PommConnection $connection, ClientHolder $client_holder, $stamp)
    {
        $session_class = $this->configuration->getParameter('class:session', '\PommProject\Foundation\Session\Session');

        $session = new $session_class($connection, $client_holder, $stamp);

        // we need our connection to know the session in order to be able to
        // use the converters, sad but true story
        if ($connection instanceof Connection) {
            $connection->setSession($session);
        }

        return $session;
    }

    /**
     * {@inheritdoc}
     */
    protected function createConnection($dsn, $connectionConfiguration)
    {
        // sorry for this, but in order to correctly cast mysql types, we do
        // need the connection to know the converters
        return new Connection($dsn, $connectionConfiguration, $this);
    }
}
