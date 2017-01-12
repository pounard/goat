<?php

namespace Goat\Core\Converter\Impl;

use Goat\Core\Converter\ConverterInterface;

class BooleanConverter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(string $type, string $value)
    {
        return (bool)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function extract(string $type, $value) : string
    {
        return $value ? '1' : '0';
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast(string $type) : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function cast(string $type)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess($value) : bool
    {
        return is_bool($value);
    }
}
