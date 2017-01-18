<?php

declare(strict_types=1);

namespace Goat\Mapper\Entity;

/**
 * Default entity interface for people that do not wish to use custom class
 * hydration still want consistent objects
 */
interface EntityInterface
{
    /**
     * Get type for field
     *
     * @param string $name
     *
     * @return string
     */
    public function getType(string $name) : string;

    /**
     * Get al types
     *
     * @return array
     */
    public function getAllTypes() : array;

    /**
     * Has the property any value
     *
     * @param string $name
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     *   If property is not defined
     */
    public function has(string $name) : bool;

    /**
     * Does the property is defined, even it has no values
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists(string $name) : bool;

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
    public function get(string $name);

    /**
     * Get all values as an array
     *
     * @return mixed[]
     *   Keys are field names, values are values
     */
    public function getAll() : array;
}
