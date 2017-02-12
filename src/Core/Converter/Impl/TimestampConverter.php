<?php

declare(strict_types=1);

namespace Goat\Core\Converter\Impl;

use Goat\Core\Converter\ConverterInterface;
use Goat\Core\Error\TypeConversionError;

class TimestampConverter implements ConverterInterface
{
    const TS_FORMAT = 'Y-m-d H:i:s.uP';
    const TS_FORMAT_DATE = 'Y-m-d';
    const TS_FORMAT_TIME = 'H:i:s.uP';
    const TS_FORMAT_TIME_INT = 'H:I:S';

    protected function formatDate($value)
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new TypeConversionError(sprintf("given value '%s' is not instanceof \DateTimeInterface", $value));
        }

        return $value->format(self::TS_FORMAT_DATE);
    }

    protected function formatTimestamp($value)
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new TypeConversionError(sprintf("given value '%s' is not instanceof \DateTimeInterface", $value));
        }

        return $value->format(self::TS_FORMAT);
    }

    protected function formatTime($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(self::TS_FORMAT_TIME);
        }

        if ($value instanceof \DateInterval) {
            return $value->format(self::TS_FORMAT_TIME_INT);
        }

        throw new TypeConversionError(sprintf("given value '%s' is not instanceof \DateTimeInterface not \DateInterval", $value));
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
    {
        $data = trim($value);

        // Time is supposed to be standard, so
        return $data ? new \DateTime($data) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value) : string
    {
        if (null === $value) {
            return sprintf("null", $type);
        }

        switch ($type) {

            case 'date':
                return $this->formatDate($value);

            case 'timestamp':
            case 'timestampz':
                return $this->formatTimestamp($value);

            case 'time':
                return $this->formatTime($value);
        }

        throw new TypeConversionError(sprintf("cannot process type '%s'", $type));
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
        switch ($type) {

            case 'date':
                return 'date';

            case 'time':
                return 'time';

            default:
                return 'timestamp';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess($value) : bool
    {
        return $value instanceof \DateTimeInterface;
    }
}
