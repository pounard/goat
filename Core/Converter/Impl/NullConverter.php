<?php

namespace Momm\Core\Converter\Impl;

use Momm\Core\Converter\ConverterInterface;

class NullConverter implements ConverterInterface
{
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
        return (string)$value;
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
