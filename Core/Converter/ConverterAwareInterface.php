<?php

namespace Goat\Core\Converter;

interface ConverterAwareInterface
{
    /**
     * Set connection
     *
     * @param ConverterInterface $connection
     *
     * @return $this
     */
    public function setConverter(ConverterInterface $converter);
}
