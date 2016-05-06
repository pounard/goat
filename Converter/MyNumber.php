<?php

namespace Momm\Foundation\Converter;

use PommProject\Foundation\Converter\PgNumber;

class MyNumber extends PgNumber implements MyConverterInterface
{
    public function needsCast()
    {
        return true;
    }

    public function castAs($type)
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

            case 'oid':
                return 'unsigned integer';

            default:
                return 'signed integer';
        }
    }
}
