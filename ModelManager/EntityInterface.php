<?php

namespace Goat\ModelManager;

interface EntityInterface
{
    const STATUS_NONE = 0;
    const STATUS_EXIST = 1;
    const STATUS_MODIFIED = 2;

    /**
     * Is entity new
     *
     * @return boolean
     */
    public function isNew();

    /**
     * Is entity modified
     *
     * @return boolean
     */
    public function isModified();

    /**
     * Set entity status
     *
     * @param int $status
     *
     * @return $this
     */
    public function toggleStatus($status = null);

    /**
     * Has the property any value
     *
     * @param string $name
     *
     * @return boolean
     *
     * @throws \InvalidArgumentException
     *   If property is not defined
     */
    public function has($name);

    /**
     * Does the property is defined, even it has no values
     *
     * @param string $name
     *
     * @return boolean
     */
    public function exists($name);

    /**
     * Get property value
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     *   If property is not defined
     */
    public function get($name);

    /**
     * Remove property value
     *
     * @param string $name
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     *   If property is not defined
     */
    public function remove($name);

    /**
     * Set property value
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     *   If property is not defined
     */
    public function set($name, $value);

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
    public function setAll($values);
}
