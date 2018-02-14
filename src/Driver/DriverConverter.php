<?php

namespace Goat\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Query\Writer\EscaperInterface;

class DriverConverter implements ConverterInterface
{
    private $default;
    private $escaper;

    public function __construct(ConverterInterface $default, EscaperInterface $escaper)
    {
        $this->default = $default;
        $this->escaper = $escaper;
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value) : string
    {
        return $this->default->guessType($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
    {
        if ('bytea' === $type || 'blob' === $type) {
            return $this->escaper->unescapeBlob($value);
        }

        return $this->default->fromSQL($type, $value);

    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value) : ?string
    {
        if ('bytea' === $type || 'blob' === $type) {
            return $this->escaper->escapeBlob($value);
        }

        return $this->default->toSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast(string $type) : bool
    {
        return $this->default->needsCast($type);
    }

    /**
     * {@inheritdoc}
     */
    public function cast(string $type) : ?string
    {
        return $this->default->cast($type);
    }
}
