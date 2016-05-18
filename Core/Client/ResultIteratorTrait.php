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
     * Fetch given field in the first or current row
     *
     * @param string $name
     *   If none given, just take the first one
     *
     * @return mixed[]
     */
    public function fetchField($name = null)
    {
        foreach ($this as $row) {
            if ($name) {
                if (!array_key_exists($name, $row)) {
                    throw new \InvalidArgumentException("invalid column '%s'", $name);
                }
                return $row[$name];
            }
            return reset($row);
        }
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
