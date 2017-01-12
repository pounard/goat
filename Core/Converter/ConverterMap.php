<?php

namespace Goat\Core\Converter;

use Goat\Core\Converter\Impl\StringConverter;
use Goat\Core\DebuggableTrait;
use Goat\Core\Error\ConfigurationError;

class ConverterMap
{
    use DebuggableTrait;

    private $aliasMap = [];
    private $converters = [];
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
     * @param string $type
     * @param ConverterInterface $instance
     * @param string[] $aliases
     *
     * @return $this
     */
    public function register($type, ConverterInterface $instance, array $aliases = [])
    {
        if (is_array($type)) {
            trigger_error(sprintf("registering type as array is outdate and will be removed, while registering %s", implode(', ', $type)), E_USER_DEPRECATED);

            $aliases = $type;
            $type = array_shift($aliases);
        }

        if (isset($this->converters[$type])) {
            $message = sprintf("type '%s' is already defined, using '%s' converter class", $type, get_class($this->converters[$type]));
            if ($this->debug) {
                throw new ConfigurationError($message);
            } else {
                trigger_error($message, E_USER_WARNING);
            }
        }

        $this->converters[$type] = $instance;

        if ($aliases) {
            foreach ($aliases as $alias) {

                $message = null;
                if (isset($this->converters[$alias])) {
                    $message = sprintf("alias '%s' for type '%s' is already defined as a type, using '%s' converter class", $alias, $type, get_class($this->converters[$type]));
                } else if (isset($this->aliasMap[$alias])) {
                    $message = sprintf("alias '%s' for type '%s' is already defined, for type '%s'", $alias, $type, get_class($this->aliasMap[$type]));
                }
                if ($message) {
                    if ($this->debug) {
                        throw new ConfigurationError($message);
                    } else {
                        trigger_error($message, E_USER_WARNING);
                    }
                }

                $this->aliasMap[$alias] = $type;
            }
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
        if (isset($this->aliasMap[$type])) {
            $type = $this->aliasMap[$type];
        }

        if (!isset($this->converters[$type])) {
            if ($this->debug || !$this->fallback) {
                throw new ConfigurationError(sprintf("no converter registered for type '%s'", $type));
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

    /**
     * Is there a type registered with this name
     *
     * @param string $type
     * @param bool $allowAliases
     *
     * @return bool
     */
    public function typeExists($type, $allowAliases = true)
    {
        return isset($this->converters[$type]) || ($allowAliases && isset($this->aliasMap[$type]));
    }

    /**
     * Is the given type an alias
     *
     * @param string $type
     *
     * @return bool
     */
    public function isTypeAlias($type)
    {
        return isset($this->aliasMap[$type]);
    }

    /**
     * Proceed to optimistic conversion using the first converter that accepts
     * the given value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function guess($value)
    {
        if (null === $value) {
            return null;
        }

        foreach ($this->converters as $type => $converter) {
            if ($converter->canProcess($value)) {
                return $converter->extract($type, $value);
            }
        }

        return $value;
    }
}
