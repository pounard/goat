<?php

namespace Momm\Core\Converter\Impl;

use Momm\Core\Converter\ConverterInterface;
use Momm\Core\Client\ConnectionAwareInterface;
use Momm\Core\Client\ConnectionAwareTrait;

class StringConverter implements ConverterInterface, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function hydrate($type, $value)
    {
        return (string)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($type, $value)
    {
        return (string)$this->connection->escapeLiteral($value);
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast($type)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cast($type)
    {
    }
}
