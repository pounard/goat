<?php

namespace Momm\Foundation\Converter;

interface MyConverterInterface
{
    /**
     * Should the value needs casting
     *
     * @return boolean
     */
    public function needsCast();

    /**
     * MySQL type the value should be casted
     *
     * @param string $type
     *   Converter type specified by the user within the query
     *
     * @return string
     */
    public function castAs($type);
}

