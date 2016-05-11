<?php

namespace Momm\ModelManager;

trait EntityTrait
{
    use StatefulEntityTrait;

    /**
     * @var mixed[]
     */
    private $values = [];

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
    protected function getFieldName($name)
    {
        if (!array_key_exists($name, $this->values)) {
            throw new \InvalidArgumentException(sprintf("property '%s' is not defined", $name));
        }

        return $name;
    }

    /**
     * Define given properties.
     *
     * @param string $fields
     *   Keys are field names, values are values to initialize with
     */
    public function defineAll(array $fields)
    {
        $this->values = array_merge($this->values, $fields);
    }

    /**
     * Has the property any value
     *
     * @param string $name
     *
     * @return boolean
     */
    public function has($name)
    {
        return isset($this->values[$this->getFieldName($name)]);
    }

    /**
     * Does the property exists (it may be null)
     *
     * @param string $name
     *
     * @return boolean
     */
    public function exists($name)
    {
        return array_key_exists($name, $this->values);
    }

    /**
     * Get property value
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        return $this->values[$this->getFieldName($name)];
    }

    /**
     * Remove property value
     *
     * @param string $name
     *
     * @return $this
     */
    public function remove($name)
    {
        $this->set($name, null);
    }

    /**
     * Set property value
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function set($name, $value)
    {
        $this->touch();

        $this->values[$this->getFieldName($name)] = $value;
    }

    /**
     * Set multiple values at once
     *
     * @param mixed[] $values
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     *   If property is not defined
     */
    public function setAll($values)
    {
        $this->touch();

        foreach ($this->values as $name => $value) {
            $this->values[$this->getFieldName($name)] = $value;
        }
    }
}
