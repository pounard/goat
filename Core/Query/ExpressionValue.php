<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;

/**
 * Represents a raw value
 */
class ExpressionValue implements Expression
{
    private $type;
    private $value;

    /**
     * Default constructor
     *
     * @param mixed $value
     */
    public function __construct($value, $type = null)
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get value type
     *
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        $ret = new ArgumentBag();
        $ret->add($this->value, $this->type);

        return $ret;
    }
}
