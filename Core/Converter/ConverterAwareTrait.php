<?php

namespace Goat\Core\Converter;

trait ConverterAwareTrait
{
    /**
     * @var ConverterInterface
     */
    protected $converter;

    /**
     * Set connection
     *
     * @param ConverterInterface $connection
     *
     * @return $this
     */
    public function setConverter(ConverterInterface $converter)
    {
        $this->converter = $converter;

        return $this;
    }
}
