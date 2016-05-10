<?php

namespace Momm\Core\Converter\Impl;

use Momm\Core\Converter\ConverterInterface;

class DecimalConverter implements ConverterInterface
{
    /**
     * From the given raw SQL string, get the PHP value
     *
     * @param string $type
     * @param string $value
     *
     * @return mixed
     */
    public function hydrate($type, $value)
    {
        
    }

    /**
     * From the given PHP value, get the raw SQL string
     *
     * @param string $type
     * @param mixed $value
     *
     * @return string
     */
    public function extract($type, $value)
    {
        
    }


    public function needsCast($type)
    {
        return true;
    }

    /**
     * Get MySQL type name to cast to
     *
     * @param string $type
     *
     * @return string
     */
    public function cast($type)
    {
        switch ($type) {

            case 'int2':
                return 'signed integer';

            case 'int4':
                return 'signed integer';

            case 'int8':
                return 'signed integer';

            case 'numeric':
                return 'integer';

            case 'float4':
                return 'decimal';

            case 'float8':
                return 'decimal';

            default:
                return 'signed integer';
        }
    }
}
