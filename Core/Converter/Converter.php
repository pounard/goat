<?php

namespace Momm\Core\Converter;

use Momm\Core\DebuggableTrait;
use Momm\Core\Converter\Impl\StringConverter;

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
    private $fallback;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->setFallback(new StringConverter());
    }

    /**
     * Set fallback converter
     *
     * @param string $fallback
     *
     * @return $this
     */
    public function setFallback(ConverterInterface $converter)
    {
        $this->fallback = $converter;

        return $this;
    }

    /**
     * Register a converter
     *
     * @param string|string[] $type
     * @param ConverterInterface $instance
     *
     * @return $this
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

        return $this;
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
            if ($this->debug || !$this->fallback) {
                throw new \InvalidArgumentException(sprintf("no converted registered for type '%s'", $type));
            }

            return $this->fallback;
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
