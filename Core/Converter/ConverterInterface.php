<?php

namespace Momm\Core\Converter;

interface ConverterInterface
{
    /**
     * From the given raw SQL string, get the PHP value
     *
     * @param string $type
     * @param string $value
     *
     * @return mixed
     */
    public function hydrate($type, $value);

    /**
     * From the given PHP value, get the raw SQL string
     *
     * @param string $type
     * @param mixed $value
     *
     * @return string
     */
    public function extract($type, $value);

    /**
     * Should this converter needs to cast the value to MySQL
     *
     * @param string $type
     *
     * @return boolean
     */
    public function needsCast($type);

    /**
     * Get MySQL type name to cast to
     *
     * @param string $type
     *
     * @return string
     */
    public function cast($type);
}
