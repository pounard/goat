<?php

declare(strict_types=1);

namespace Goat\Converter;

interface ConverterAwareInterface
{
    /**
     * Set converter map
     *
     * @param ConverterMap $converter
     */
    public function setConverter(ConverterMap $converter);
}
