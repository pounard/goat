<?php

namespace Momm\Foundation\Session;

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
    protected function createConnection($dsn, $connectionConfiguration)
    {
        return new Connection($dsn, $connectionConfiguration);
    }
}
