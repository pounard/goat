<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareTrait;
use Goat\Core\DebuggableTrait;
use Goat\Core\Error\InvalidDataAccessError;
use Goat\Core\Hydrator\HydratorAwareTrait;

abstract class AbstractResultIterator implements ResultIteratorInterface
{
    use DebuggableTrait;
    use ConverterAwareTrait;
    use HydratorAwareTrait;

    /**
     * Convert values from SQL types to PHP native types
     *
     * @param string[] $row
     *   SQL fetched raw values are always strings
     *
     * @return mixed[]
     *   Same array, with converted values
     */
    protected function convertValues(array $row) : array
    {
        if (!$this->converter) {
            trigger_error("result iterator has no converter set", E_USER_WARNING);

            return $row;
        }

        $ret = [];

        foreach ($row as $name => $value) {
            // @todo find a better way to deal with null values, maybe
            //    something like:
            //      - add a getNullValue() on ConverterInterface?
            //      - or just return null and let fail?
            if (null !== $value) {
                $ret[$name] = $this
                    ->converter
                    ->hydrate(
                        $this->getColumnType($name),
                        $value
                    )
                ;
            }
        }

        return $ret;
    }

    /**
     * Hydrate row using the iterator object hydrator
     *
     * @param mixed[] $row
     *   PHP native types converted values
     *
     * @return array|object
     *   Raw object, return depends on the hydrator
     */
    protected function hydrate(array $row)
    {
        $converted = $this->convertValues($row);

        if ($this->hydrator) {
            return $this->hydrator->createAndHydrateInstance($converted);
        }

        return $converted;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchField($name = null)
    {
        foreach ($this as $row) {
            if ($name) {
                if (!array_key_exists($name, $row)) {
                    throw new InvalidDataAccessError("invalid column '%s'", $name);
                }
                return $row[$name];
            }
            return reset($row);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->countRows();
    }
}
