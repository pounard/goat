<?php

declare(strict_types=1);

namespace Goat\Hydrator;

use Goat\Mapper\Entity\DefaultEntity;

final class HydratorMap
{
    private $cacheDir;
    private $configuration;
    private $hydrators = [];
    private $typeMap = [];

    /**
     * Defalut constructor
     *
     * @param string $cacheDir
     */
    public function __construct(string $cacheDir = null)
    {
        if (!$cacheDir) {
            $cacheDir = sys_get_temp_dir().'/goat-hydrator';
            if (!is_dir($cacheDir) && !@mkdir($cacheDir)) { // Attempt directory creation
                throw new \InvalidArgumentException(sprintf("'%s': could not create directory", $cacheDir));
            }
        }

        $this->cacheDir = $cacheDir;
        $this->hydrators[DefaultEntity::class] = new DefaultEntityHydrator();
    }

    /**
     * Set configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Get configuration
     */
    public function getConfiguration() : Configuration
    {
        if (!$this->configuration) {
            $this->configuration = new Configuration();
        }

        return $this->configuration;
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
     *
     * @internal
     *   Do not use this
     */
    public function getRealHydrator(string $class) : HydratorInterface
    {
        return $this->createHydrator($class);
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
            return $this->hydrators[$class] = new HierarchicalHydrator($class, $this);
        }

        return $this->hydrators[$class];
    }
}
