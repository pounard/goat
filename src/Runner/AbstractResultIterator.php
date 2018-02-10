<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterAwareTrait;
use Goat\Error\InvalidDataAccessError;
use Goat\Hydrator\HydratorAwareTrait;
use Goat\Error\QueryError;

abstract class AbstractResultIterator implements ResultIteratorInterface
{
    use ConverterAwareTrait;
    use HydratorAwareTrait;

    protected $columnKey;

    /**
     * Convert a single value
     *
     * @param string $name
     * @param mixed $value
     *
     * @return mixed
     */
    protected function convertValue(string $name, $value)
    {
        return $this->converter->fromSQL($this->getColumnType($name), $value);
    }

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
            if (null !== $value) {
                $ret[$name] = $this->convertValue((string)$name, $value);
            } else {
                $ret[$name] = null;
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
    public function setKeyColumn(string $name) : ResultIteratorInterface
    {
        if (!$this->columnExists($name)) {
            throw new QueryError(sprintf("column '%s' does not exist in result", $name));
        }

        $this->columnKey = $name;

        return $this;
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
