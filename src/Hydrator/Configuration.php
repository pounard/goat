<?php

declare(strict_types=1);

namespace Goat\Hydrator;

use Doctrine\Common\Annotations\Reader;
use Goat\Hydrator\Annotation\Property;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;

/**
 * Hierarchical hydrator configuration: carries knowledge about which properties
 * of which classes are instances of which other class.
 */
final class Configuration
{
    /**
     * Creates a default property info extractor
     *
     * Beware this is a non-cached implementation per default, and might come
     * with a serious performance penalty.
     *
     * If you are using a framework, inject a cached one using the
     * setPropertyInfoReader() method instead.
     */
    static public function createPropertyInfoExtractor() : PropertyInfoExtractorInterface
    {
        $reflexionExtractor = new ReflectionExtractor();
        $phpDocExtractor = new PhpDocExtractor();

        return new PropertyInfoExtractor([$reflexionExtractor], [$reflexionExtractor, $phpDocExtractor]);
    }

    private $annotationsReader;
    private $propertyClassMap = [];
    private $propertyInfoExtractor;

    /**
     * Default constructor
     */
    public function __construct(array $propertyClassMap = [])
    {
        $this->propertyClassMap = $this->sanitizeConfigurationInput($propertyClassMap);

        // Attempt to auto configure as possible this instance,
        if (class_exists(PropertyInfoExtractor::class)) {
            $this->propertyInfoExtractor = self::createPropertyInfoExtractor();
        }
    }

    /**
     * Normalize names, when using FQDN you may experience a few behaviours:
     *
     *  - If using PHP's own CLASS::class constant, FQDN will not have the
     *    leading namespace separator
     *
     *  - If manually wrote by a developer, it may contain it
     *
     * To be consistent with PHP we simply always drop the leading namespace
     * separator, this will also be the case in the other methods for which
     * input could come from anywhere.
     */
    private function sanitizeConfigurationInput(array $input) : array
    {
        foreach ($input as $className => $value) {
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
                unset($input[$className]);
                $className = ltrim($className, '\\');
                $modified = true;
            }

            if ($modified) {
                $input[$className] = $value;
            }
        }

        return $input;
    }

    /**
     * Set annotation reader
     */
    public function setAnnotationReader(Reader $annotationReader)
    {
        $this->annotationsReader = $annotationReader;
    }

    /**
     * Set property info extractor
     */
    public function setPropertyInfoReader(PropertyInfoExtractorInterface $propertyInfoExtractor)
    {
        $this->propertyInfoExtractor = $propertyInfoExtractor;
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
        $ret = [];

        // Attempt a pass using annotation reader
        if ($this->annotationsReader) {
            $refClass = new \ReflectionClass($className);
            /** @var \ReflectionProperty $refProperty */
            foreach ($refClass->getProperties() as $refProperty) {
                $annotation = $this->annotationReader->getPropertyAnnotation($refProperty, Property::class);
                if ($annotation instanceof Property) {
                    $ret[$refProperty->getName()] = $annotation->getClassName();
                }
            }
        }

        // Use Symfony's property info extractor if available
        if ($properties = $this->propertyInfoExtractor->getProperties($className)) {
            foreach ($properties as $property) {
                if ($types = $this->propertyInfoExtractor->getTypes($className, $property)) {
                    foreach ($types as $type) {
                        if ($propertyClassName = $type->getClassName()) {
                            $ret[$property] = $propertyClassName;
                            break; // Proceed with next property
                        }
                    }
                }
            }
        }

        return $ret;
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
