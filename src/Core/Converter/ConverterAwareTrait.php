<?php

declare(strict_types=1);

namespace Goat\Core\Converter;

trait ConverterAwareTrait
{
    /**
     * @var ConverterMap
     */
    protected $converter;

    /**
     * Set connection
     *
     * @param ConverterMap $connection
     */
    public function setConverter(ConverterMap $converter)
    {
        $this->converter = $converter;
    }
}
