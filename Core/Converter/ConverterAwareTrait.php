<?php

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
     *
     * @return $this
     */
    public function setConverter(ConverterMap $converter)
    {
        $this->converter = $converter;

        return $this;
    }
}
