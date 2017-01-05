<?php

namespace Goat\Core\Converter\Impl;

use Goat\Core\Converter\ConverterInterface;

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
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cast($type)
    {
    }
}
