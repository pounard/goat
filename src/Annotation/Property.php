<?php

declare(strict_types=1);

namespace Goat\Hydrator\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 * @Attributes({
 *   @Attribute("class", type = "string", required = true),
 * })
 */
class Property
{
    private $className;

    public function __construct(array $values)
    {
        $this->className = $values['class'];
    }

    public function getClassName() : string
    {
        return $this->className ?? '';
    }
}
