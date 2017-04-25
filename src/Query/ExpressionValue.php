<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\ArgumentBag;

/**
 * Represents a raw value
 */
final class ExpressionValue implements Expression
{
    private $name;
    private $type;
    private $value;

    /**
     * Default constructor
     *
     * @param mixed $value
     * @param string $type
     */
    public function __construct($value, string $type = null)
    {
        if (null === $type) {
            if (is_string($value) && $value &&  ':' === $value[0]) {

                // Attempt to find type by convention
                if (false !== strpos($value, '::')) {
                    list($name, $type) = explode('::', $value, 2);
                } else {
                    $name = $value;
                }

                $this->name = substr($name, 1);

                // Value cannot exist from this point, really, since we just
                // gave name and type information; query will need to be send
                // with parameters along
                $value = null;
            }
        }

        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Get value
     *
     * @return mixed
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
     * Get value name, if any
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        $ret = new ArgumentBag();
        $ret->add($this->value, $this->name, $this->type);

        return $ret;
    }
}
