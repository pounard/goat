<?php

namespace Goat\Core\Converter\Impl;

use Goat\Core\Converter\ConverterInterface;

class DecimalConverter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate($type, $value)
    {
        return (float)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($type, $value)
    {
        return (float)$value;
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
        return 'decimal';
    }
}
