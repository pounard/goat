<?php

namespace Goat\Core\Converter;

interface ConverterAwareInterface
{
    /**
     * Set connection
     *
     * @param ConverterMap $connection
     *
     * @return $this
     */
    public function setConverter(ConverterMap $converter);
}
