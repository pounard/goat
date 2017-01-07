<?php

namespace Goat\Core\Converter\Impl;

use Goat\Core\Converter\ConverterInterface;

class BooleanConverter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate($type, $value)
    {
        return (bool)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($type, $value)
    {
        return (int)(bool)$value;
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
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess($value)
    {
        return is_bool($value);
    }
}
