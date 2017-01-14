<?php

namespace Goat\Core\Converter\Impl;

use Goat\Core\Converter\ConverterInterface;

class DecimalConverter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, string $value)
    {
        return (float)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value) : string
    {
        return (string)(float)$value;
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
        return is_numeric($value);
    }
}
