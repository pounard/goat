<?php

namespace Momm\Core\Converter\Impl;

use Momm\Core\Converter\ConverterInterface;
use Momm\Core\Client\EscaperAwareInterface;
use Momm\Core\Client\EscaperAwareTrait;

class StringConverter implements ConverterInterface, EscaperAwareInterface
{
    use EscaperAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function hydrate($type, $value)
    {
        return (string)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($type, $value)
    {
        if (!$this->escaper) {
            throw new \LogicException(sprintf("I won't let you escape any string without any viable API to escape it, this is a serious security issue."));
        }
        //return (string)$this->escaper->escapeLiteral($value);
        // We are actually using PDO in prepare emulation mode, we don't need
        // to actually any strings.
        return (string)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast($type)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cast($type)
    {
    }
}
