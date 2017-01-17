<?php

namespace Goat\Core\Converter\Impl;

use Goat\Core\Converter\ConverterInterface;

class StringConverter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, string $value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value) : string
    {
        return (string)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast(string $type) : bool
    {
        return false;
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
        return is_string($value);
    }
}
