<?php

declare(strict_types=1);

namespace Goat\Hydrator;

use GeneratedHydrator\Configuration;

/**
 * Hydrates objects using Ocramius's Generated Hydrator API
 */
final class GeneratedHydrator extends AbstractHydrator
{
    private $configuration;
    private $hydrator;

    /**
     * Default constructor
     *
     * @param string $className
     * @param string $cacheDir
     */
    public function __construct(string $className, string $cacheDir = null)
    {
        parent::__construct($className);

        $this->configuration = new Configuration($className);

        if ($cacheDir) {
            $this->configuration->setGeneratedClassesTargetDir($cacheDir);
        }

        $hydratorName = $this->configuration->createFactory()->getHydratorClass();
        $this->hydrator = new $hydratorName();
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateInstance($values, $object)
    {
        $this->hydrator->hydrate($values, $object);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractFromInstance($object) : array
    {
        return $this->hydrator->extract($object);
    }
}
