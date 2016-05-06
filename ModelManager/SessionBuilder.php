<?php

namespace Momm\ModelManager;

use Momm\Foundation\SessionBuilder as MommFoundationSessionBuilder;
use Momm\ModelManager\Session as MommModelManagerSession;

use PommProject\Foundation\Client\ClientHolder;
use PommProject\Foundation\Session\Connection;
use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Model\ModelPooler;
use PommProject\ModelManager\ModelLayer\ModelLayerPooler;

class SessionBuilder extends MommFoundationSessionBuilder
{
    /**
     * {@inheritdoc}
     */
    protected function postConfigure(Session $session)
    {
        parent::postConfigure($session);

        $session
            ->registerClientPooler(new ModelPooler())
            ->registerClientPooler(new ModelLayerPooler())
        ;

        return $this;
    }

    /**
     * Create session
     *
     * @param Connection $connection
     * @param ClientHolder $client_holder
     * @param null|string $stamp
     *
     * @return MommModelManagerSession
     */
    protected function createSession(Connection $connection, ClientHolder $client_holder, $stamp)
    {
        $this->configuration->setDefaultValue('class:session', MommModelManagerSession::class);

        return parent::createSession($connection, $client_holder, $stamp);
    }
}
