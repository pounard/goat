<?php

namespace Goat\Core\Converter;

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
    public function hydrate(string $type, string $value);

    /**
     * From the given PHP value, get the raw SQL string
     *
     * @param string $type
     * @param mixed $value
     *
     * @return string
     */
    public function extract(string $type, $value) : string;

    /**
     * Should this converter needs to cast the value to the server
     *
     * @param string $type
     *
     * @return bool
     */
    public function needsCast(string $type) : bool;

    /**
     * Get SQL type name to cast to
     *
     * @param string $type
     *
     * @return null|string
     *   You may return null if you consider that the given type is valid
     *   for cast, and let the server handle it as-is
     */
    public function cast(string $type);

    /**
     * Can this value be processed
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function canProcess($value) : bool;
}
