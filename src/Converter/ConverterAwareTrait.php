<?php

declare(strict_types=1);

namespace Goat\Converter;

trait ConverterAwareTrait
{
    /**
     * @var ConverterInterface
     */
    protected $converter;

    /**
     * Set converter
     *
     * @param ConverterInterface $converter
     */
    public function setConverter(ConverterInterface $converter)
    {
        $this->converter = $converter;
    }
}
