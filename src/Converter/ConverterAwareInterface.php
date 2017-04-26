<?php

declare(strict_types=1);

namespace Goat\Converter;

interface ConverterAwareInterface
{
    /**
     * Set connection
     *
     * @param ConverterMap $connection
     */
    public function setConverter(ConverterMap $converter);
}
