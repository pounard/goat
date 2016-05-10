<?php

namespace Momm\Core\Converter;

use Momm\Core\DebuggableTrait;
use Momm\Core\Converter\Impl\NullConverter;

class Converter implements ConverterInterface
{
    use DebuggableTrait;

    /**
     * @var ConverterInterface[]
     */
    private $converters = [];

    /**
     * @var ConverterInterface
     */
    private $nullConverter;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->nullConverter = new NullConverter();
    }

    /**
     * Register a converter
     *
     * @param string|string[] $type
     * @param ConverterInterface $instance
     */
    public function register($types, ConverterInterface $instance)
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        foreach ($types as $type) {

            if (isset($this->converters[$type])) {
                $message = sprintf("type '%s' is already defined, using '%s' converter class", $type, get_class($this->types[$type]));
                if ($this->debug) {
                    throw new \InvalidArgumentException($message);
                } else {
                    trigger_error($message, E_USER_WARNING);
                }
            }

            $this->converters[$type] = $instance;
        }
    }

    /**
     * Get converter for type
     *
     * @param string $type
     *
     * @return ConverterInterface
     */
    final protected function get($type)
    {
        if (!isset($this->converters[$type])) {
            if ($this->debug) {
                throw new \InvalidArgumentException(sprintf("no converted registered for type '%s'", $type));
            }

            return $this->nullConverter;
        }

        return $this->converters[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($type, $value)
    {
        return $this->get($type)->hydrate($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function extract($type, $value)
    {
        return $this->get($type)->extract($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast($type)
    {
        return $this->get($type)->needsCast($type);
    }

    /**
     * {@inheritdoc}
     */
    public function cast($type)
    {
        return $this->get($type)->cast($type);
    }
}
