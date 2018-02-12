<?php

declare(strict_types=1);

namespace Goat\Hydrator;

use Goat\Error\GoatError;

/**
 * Hydrates objects and nested objects altogether
 */
final class HierarchicalHydrator implements HydratorInterface
{
    private $className;
    private $configuration;
    private $hydratorMap;

    /**
     * Default constructor
     */
    public function __construct(string $class, HydratorMap $hydratorMap, HierarchicalHydratorConfiguration $configuration = null)
    {
        $this->className = $class;
        $this->hydratorMap = $hydratorMap;

        if (!$configuration) {
            // Fallback on an empty instance that will attempt runtime lookups
            $this->configuration = new HierarchicalHydratorConfiguration();
        }
    }

    /**
     * Aggregate properties under the given group
     *
     * @param string $group
     *   Group name, key values name prefixes
     * @param mixed[] $values
     *   Values being hydrated
     *
     * @todo
     *   current implementation is, I afraid, inneficient, but let's work
     *   with this right now
     *
     * @return mixed[]
     */
    private function aggregatePropertiesOf(string $group, array $values) : array
    {
        $ret = [];
        $length = strlen($group);

        foreach ($values as $key => $value) {
            if (substr($key, 0, $length + 1) === $group.'.') {
                $ret[substr($key, $length + 1)] = $value;
            }
        }

        return $ret;
    }

    /**
     * Create nested objects and set them in the new returned dataset
     */
    private function aggregateNestedProperties(array $values) : array
    {
        foreach ($this->configuration->getClassPropertyMap($this->className) as $property => $className) {

            if (array_key_exists($property, $values)) {
                throw new GoatError(sprintf(
                    "nested property '%s' with class '%s' already has a value: '%s'",
                    $property, $className, $values[$property]
                ));
            }

            if ($nestedValues = $this->aggregatePropertiesOf($property, $values)) {
                $values[$property] = $this
                    ->hydratorMap
                    ->get($className)
                    ->createAndHydrateInstance(
                        $nestedValues,
                        $this->configuration->getConstructorMode($className)
                    )
                ;
            }
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function createAndHydrateInstance(array $values, int $constructor = HydratorInterface::CONSTRUCTOR_LATE)
    {
        return $this
            ->hydratorMap
            ->getRealHydrator($this->className)
            ->createAndHydrateInstance(
                $this->aggregateNestedProperties($values),
                $constructor
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateObject(array $values, $object)
    {
        return $this
            ->hydratorMap
            ->getRealHydrator($this->className)
            ->hydrateObject(
                $this->aggregateNestedProperties($values),
                $object
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function extractValues($object) : array
    {
        // @todo implement me properly
        return $this->hydratorMap->getRealHydrator($this->className)->extractValues($object);
    }
}
