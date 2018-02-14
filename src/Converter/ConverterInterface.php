<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * Public facade for the converter subsystem
 */
interface ConverterInterface
{
    const TYPE_NULL = 'null';
    const TYPE_UNKNOWN = '_';

    /**
     * From the given raw SQL string, get the PHP value
     *
     * @param string $type
     * @param mixed $value
     *   This can't be type hinted, because some drivers will convert
     *   scalar types by themselves
     *
     * @return mixed
     */
    public function fromSQL(string $type, $value);

    /**
     * From the given PHP value, get the raw SQL string
     *
     * @param string $type
     * @param mixed $value
     *
     * @return string
     */
    public function toSQL(string $type, $value) : ?string;

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
    public function cast(string $type) : ?string;

    /**
     * Guess type for the given value
     *
     * @param mixed $value
     *
     * @return string
     */
    public function guessType($value) : string;
}
