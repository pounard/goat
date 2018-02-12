<?php

namespace Goat\Hydrator;

/**
 * Hierarchical hydrator configuration: carries knowledge about which properties
 * of which classes are instances of which other class.
 */
final class HierarchicalHydratorConfiguration
{
    private $propertyClassMap = [];

    /**
     * Default constructor
     */
    public function __construct(array $propertyClassMap = [])
    {
        // @todo testing data
        $this->propertyClassMap = [
            '\Goat\Tests\Hydrator\HydratedNestingClass' => [
                'constructor' => HydratorInterface::CONSTRUCTOR_LATE,
                'properties' => [
                    'nestedObject1' => '\Goat\Tests\Hydrator\HydratedClass',
                    'nestedObject2' => '\Goat\Tests\Hydrator\HydratedParentClass',
                ],
            ],
            '\Goat\Tests\Hydrator\HydratedClass' => [
                'constructor' => HydratorInterface::CONSTRUCTOR_SKIP,
                'properties' => [
                    'someNestedInstance' => '\Goat\Tests\Hydrator\HydratedParentClass',
                ],
            ],
        ];

        //
        // Normalize names, when using FQDN you may experience a few behaviours:
        //
        //  - If using PHP's own CLASS::class constant, FQDN will not have the
        //    leading namespace separator
        //
        //  - If manually wrote by a developer, it may contain it
        //
        // To be consistent with PHP we simply always drop the leading namespace
        // separator, this will also be the case in the other methods for which
        // input could come from anywhere.
        //
        foreach ($this->propertyClassMap as $className => $value) {
            $modified = false;

            // Same goes, of course, for properties
            if (isset($value['properties'])) {
                foreach ($value['properties'] as $property => $nestedClassName) {
                  if ('\\' === $nestedClassName[0]) {
                      $value['properties'][$property] = ltrim($nestedClassName, '\\');
                      $modified = true;
                  }
                }
            }

            if ('\\' === $className[0]) {
                unset($this->propertyClassMap[$className]);
                $className = ltrim($className, '\\');
                $modified = true;
            }

            if ($modified) {
                $this->propertyClassMap[$className] = $value;
            }
        }
    }

    /**
     * Dynamically lookup class properties to determine their type
     *
     * @param string $className
     *   Target class name
     *
     * @return array
     *   Keys are property names, values are class names
     */
    private function dynamicPropertiesLookup(string $className) : array
    {
        return []; // @todo implement me
    }

    /**
     * Get properties to hydrate for class
     *
     * @param string $className
     *   Class name from which to find properties, it must be a fully qualified one
     *
     * @return array
     *   Keys are property names, values are class names
     */
    public function getClassPropertyMap(string $className) : array
    {
        if ('\\' === $className[0]) {
            $className = ltrim($className, '\\');
        }

        if (!isset($this->propertyClassMap[$className]['properties'])) {
            $this->propertyClassMap[$className]['properties'] = $this->dynamicPropertiesLookup($className);
        }

        return $this->propertyClassMap[$className]['properties'];
    }

    /**
     * Get constructor mode
     *
     * @param string $className
     *   Class name from which to find properties, it must be a fully qualified one
     *
     * @return int
     *   One of the \Goat\Hydrator\HydratorInterface::CONSTRUCTOR_* constants
     */
    public function getConstructorMode(string $className) : int
    {
        if ('\\' === $className[0]) {
            $className = ltrim($className, '\\');
        }

        // It's really not possible to guess which construction mode for the
        // object must be applied, because it depends upon the constructor
        // code and how the developer chose to code it.
        // In case there's nothing specified, return the default behavior.
        return (int)($this->propertyClassMap[$className]['constructor'] ?? HydratorInterface::CONSTRUCTOR_LATE);
    }
}
