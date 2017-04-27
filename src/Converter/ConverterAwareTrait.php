<?php

declare(strict_types=1);

namespace Goat\Converter;

trait ConverterAwareTrait
{
    /**
     * @var ConverterMap
     */
    protected $converter;

    /**
     * Set converter
     *
     * @param ConverterMap $converter
     */
    public function setConverter(ConverterMap $converter)
    {
        $this->converter = $converter;
    }
}
