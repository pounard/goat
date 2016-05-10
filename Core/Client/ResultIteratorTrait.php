<?php

namespace Momm\Core\Client;

use Momm\Core\DebuggableTrait;
use Momm\Core\Converter\ConverterAwareTrait;

trait ResultIteratorTrait
{
    use DebuggableTrait;
    use ConverterAwareTrait;

    /**
     * Hydrate given array
     *
     * @param string $row
     *
     * @return mixed[]
     *   Same array, with values hydrated
     */
    protected function hydrate($row)
    {
        $ret = [];

        foreach ($row as $name => $value) {
            $ret[$name] = $this
                ->converter
                ->hydrate(
                    $this->getFieldType($name),
                    $value
                )
            ;
        }

        return $ret;
    }

    /**
     * Count rows
     *
     * @return int
     */
    public function count()
    {
        return $this->countRows();
    }
}
