<?php

declare(strict_types=1);

namespace Goat\Converter;

interface ConverterAwareInterface
{
    /**
     * Set converter map
     *
     * @param ConverterInterface $converter
     */
    public function setConverter(ConverterInterface $converter);
}
