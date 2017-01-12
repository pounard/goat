<?php

namespace Goat\Core\Converter\Impl;

use Goat\Core\Client\EscaperAwareInterface;
use Goat\Core\Client\EscaperAwareTrait;
use Goat\Core\Converter\ConverterInterface;
use Goat\Core\Error\ConfigurationError;

class StringConverter implements ConverterInterface, EscaperAwareInterface
{
    use EscaperAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function hydrate(string $type, string $value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function extract(string $type, $value) : string
    {
        if (!$this->escaper) {
            throw new ConfigurationError(sprintf("I won't let you escape any string without any viable API to escape it, this is a serious security issue."));
        }
        //return (string)$this->escaper->escapeLiteral($value);
        // We are actually using PDO in prepare emulation mode, we don't need
        // to actually escape any strings.
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
    public function cast(string $type) : string
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
