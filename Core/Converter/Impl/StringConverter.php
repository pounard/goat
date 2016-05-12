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
        if (!$this->connection) {
            throw new \LogicException(sprintf("I won't let you escape any string without any viable API to escape it, this is a serious security issue."));
        }
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
