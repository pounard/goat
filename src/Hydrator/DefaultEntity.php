<?php

declare(strict_types=1);

namespace Goat\Hydrator;

/**
 * Default entity implementation
 */
class DefaultEntity implements EntityInterface
{
    const DEFAULT_TYPE = 'unknown';

    private $types = [];
    private $values = [];

    /**
     * Default constructor
     *
     * @param array $values
     * @param array $types
     */
    public function __construct(array $values, array $types = [])
    {
        $this->values = $values;
        $this->types = $types;

        if (\count($this->types) !== \count($this->values)) {
            foreach (\array_keys($this->values) as $key) {
                if (!isset($this->types[$key])) {
                    $this->types[$key] = self::DEFAULT_TYPE;
                }
            }
        }
    }

    /**
     * Get a value
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Does value exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name) : bool
    {
        return \array_key_exists($name, $this->values);
    }

    /**
     * Get field name, and validate it's defined
     *
     * @param string $name
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     *   When the field name is node defined
     */
    final protected function getFieldName(string $name) : string
    {
        if (!\array_key_exists($name, $this->values)) {
            throw new \InvalidArgumentException(\sprintf("property '%s' is not defined", $name));
        }

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(string $name) : string
    {
        if (!isset($this->types[$name])) {
            throw new \InvalidArgumentException(\sprintf("property '%s' is not defined", $name));
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTypes() : array
    {
        return $this->types;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name) : bool
    {
        return isset($this->values[$this->getFieldName($name)]);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name) : bool
    {
        return \array_key_exists($name, $this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name)
    {
        return $this->values[$this->getFieldName($name)];
    }

    /**
     * {@inheritdoc}
     */
    public function getAll() : array
    {
        return $this->values;
    }
}
