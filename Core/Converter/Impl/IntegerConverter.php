<?php

namespace Momm\Core\Converter\Impl;

use Momm\Core\Converter\ConverterInterface;

class IntegerConverter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate($type, $value)
    {
        return (int)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($type, $value)
    {
        return (int)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast($type)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function cast($type)
    {
        return 'signed integer';
    }
}
