<?php

namespace Momm\Foundation\Converter;

use PommProject\Foundation\Converter\PgTimestamp;
use PommProject\Foundation\Session\Session;

class MyTimestamp extends PgTimestamp implements MyConverterInterface
{
    const TS_FORMAT_DATE = 'Y-m-d';
    const TS_FORMAT_TIME = 'H:i:s.uP';

    public function needsCast()
    {
        return true;
    }

    public function castAs($type)
    {
        switch ($type) {

          case 'date':
              return 'date';

          case 'time':
              return 'time';

          default:
              return 'datetime';
        }
    }

    protected function toMy($data, $type)
    {
        switch ($type) {

            case 'date':
                return $this->checkData($data)->format(static::TS_FORMAT_DATE);

            case 'time':
                return $this->checkData($data)->format(static::TS_FORMAT_TIME);

            default:
                return $this->checkData($data)->format(static::TS_FORMAT);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toPg($data, $type, Session $session)
    {
        return
            $data !== null
            ? sprintf("%s '%s'", $type, $this->checkData($data)->format(static::TS_FORMAT))
            : sprintf("NULL::%s", $type)
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function toPgStandardFormat($data, $type, Session $session)
    {
        return
            $data !== null
            ? $this->checkData($data)->format(static::TS_FORMAT)
            : null
            ;
    }
}
