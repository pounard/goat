<?php

namespace Goat\Core\Converter;

interface ConverterAwareInterface
{
    /**
     * Set connection
     *
     * @param ConverterMap $connection
     */
    public function setConverter(ConverterMap $converter);
}
