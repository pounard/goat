<?php

namespace Momm\Core\Converter\Impl;

use Momm\Core\Converter\ConverterInterface;

class TimestampConverter implements ConverterInterface
{
    const TS_FORMAT = 'Y-m-d H:i:s.uP';
    const TS_FORMAT_DATE = 'Y-m-d';
    const TS_FORMAT_TIME = 'H:i:s.uP';
    const TS_FORMAT_TIME_INT = 'H:I:S';

    protected function formatDate($value)
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException(sprintf("given value '%s' is not instanceof \DateTimeInterface", $value));
        }

        return $value->format(self::TS_FORMAT_DATE);
    }

    protected function formatTimestamp($value)
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException(sprintf("given value '%s' is not instanceof \DateTimeInterface", $value));
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

        throw new \InvalidArgumentException(sprintf("given value '%s' is not instanceof \DateTimeInterface not \DateInterval", $value));
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($type, $value)
    {
        $data = trim($value);

        // Time is supposed to be standard, so
        return $data ? new \DateTime($data) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($type, $value)
    {
        if (null === $value) {
            return sprintf("null", $type);
        }

        switch ($type) {

            case 'date':
                return $this->formatDate($value);

            case 'timestamp':
                return $this->formatTimestamp($value);

            case 'time';
                return $this->formatTime($value);
        }

        throw new \InvalidArgumentException(sprintf("cannot process type '%s'", $type));
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
        switch ($type) {

          case 'date':
              return 'date';

          case 'time':
              return 'time';

          default:
              return 'datetime';
        }
    }
}
