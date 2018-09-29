<?php

declare(strict_types=1);

namespace Goat\Hydrator;

abstract class AbstractHydrator implements HydratorInterface
{
    protected $className;
    private $reflectionClass;

    /**
     * Default constructor
     *
     * @param string $className
     * @param string $cacheDir
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Hydrate the given object with the given values
     */
    abstract protected function hydrateInstance($values, $object);

    /**
     * Extract values from the given instance
     */
    abstract protected function extractFromInstance($object) : array;

    /**
     * Create object instance without constructor
     *
     * @return mixed
     */
    private function createInstanceWithoutConstructor()
    {
        if (!$this->reflectionClass) {
            if (!class_exists($this->className)) {
                // @codeCoverageIgnoreStart
                throw new \InvalidArgumentException(\sprintf("'%s' class does not exists", $this->className));
                // @codeCoverageIgnoreEnd
            }

            $this->reflectionClass = new \ReflectionClass($this->className);
        }

        return $this->reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * {@inheritdoc}
     */
    public function createAndHydrateInstance(array $values, int $constructor = HydratorInterface::CONSTRUCTOR_LATE)
    {
        if (HydratorInterface::CONSTRUCTOR_SKIP === $constructor || HydratorInterface::CONSTRUCTOR_LATE === $constructor) {
            $object = $this->createInstanceWithoutConstructor();
        } else {
            $object = new $this->className;
        }

        $this->hydrateInstance($values, $object);

        if (HydratorInterface::CONSTRUCTOR_LATE === $constructor && method_exists($object, '__construct')) {
            // @todo How about constructor arguments?
            $object->__construct();
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateObject(array $values, $object)
    {
        if (!$object instanceof $this->className) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(\sprintf("given object is not a '%s' instance", $this->className));
            // @codeCoverageIgnoreEnd
        }

        $this->hydrateInstance($values, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function extractValues($object) : array
    {
        if (!$object instanceof $this->className) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(\sprintf("given object is not a '%s' instance", $this->className));
            // @codeCoverageIgnoreEnd
        }

        return $this->extractFromInstance($object);
    }
}
