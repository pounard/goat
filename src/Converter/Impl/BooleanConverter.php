<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterInterface;

class BooleanConverter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
    {
        if (!$value) {
            return false;
        }
        if (is_numeric($value)) {
            return (bool)$value;
        }

        switch ($value) {

            case 'f';
            case 'false':
                return false;

            default;
                return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value) : string
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
