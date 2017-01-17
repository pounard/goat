<?php

declare(strict_types=1);

namespace Goat\Core\Hydrator;

final class HydratorMap
{
    private $cacheDir;
    private $hydrators = [];
    private $typeMap = [];

    /**
     * Defalut constructor
     *
     * @param string $cacheDir
     */
    public function __construct(string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * Create an hydrator instance using the default hydrator class
     *
     * @param string $class
     *   Class name
     *
     * @return HydratorInterface
     */
    private function createHydrator(string $class) : HydratorInterface
    {
        return new GeneratedHydrator($class, $this->cacheDir);
    }

    /**
     * Get hydrator for class or identifier
     *
     * @param string $class
     *   Either a class name or a class alias
     *
     * @return HydratorInterface
     */
    public function get(string $class) : HydratorInterface
    {
        if (isset($this->typeMap[$class])) {
            $class = $this->typeMap[$class];
        }

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf("'%s' class does not exists", $class));
        }

        if (!isset($this->hydrators[$class])) {
            return $this->hydrators[$class] = $this->createHydrator($class);
        }

        return $this->hydrators[$class];
    }
}
