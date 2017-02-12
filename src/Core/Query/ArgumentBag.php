<?php

declare(strict_types=1);

namespace Goat\Core\Query;

use Goat\Core\Error\QueryError;

/**
 * Stores a copy of all parameters, and matching type if any found.
 *
 * Parameters are always an ordered array, they may not be identifier from
 * within the query, but they can be in this bag.
 */
class ArgumentBag
{
    private $data = [];
    private $index = 0;
    private $nameMap = [];
    private $names = [];
    private $types = [];

    /**
     * Add a parameter
     *
     * @param mixed $value
     *   Value
     * @param string $name
     *   Named identifier, for query alteration to be possible
     * @param string $type
     *   SQL datatype
     */
    public function add($value, $name = null, $type = null)
    {
        if ($name && isset($this->nameMap[$name])) {
            throw new QueryError(sprintf("%s argument name is already in use in this query", $name));
        }

        $index = $this->index++;

        $this->data[$index] = $value;
        $this->names[$index] = $name;
        $this->types[$index] = $type;

        if ($name) {
            $this->nameMap[$name] = $index;
        }
    }

    /**
     * Get parameters array
     *
     * @param mixed[] $overrides
     *   If the current bag contains named parameters, this array should serve
     *   to replace the current values with the given ones, using the internal
     *   name map.
     *   This is where the magic happens and allow both sequential and named
     *   parameters to live altogether: per definition, parameters are always
     *   sequential, and there is no way to go arround that, but because we
     *   have an index map of names, we can proceed to replacements in the
     *   returned array.
     *
     * @return $data
     */
    public function getAll(array $overrides = [])
    {
        if (!$overrides) {
            return $this->data;
        }

        $ret = $this->data;

        foreach ($overrides as $name => $value) {
            if (is_int($name)) {
                $index = $name;
            } else {
                if (!isset($this->nameMap[$name])) {
                    throw new QueryError(sprintf("named argument %s does not exist in the current query", $name));
                }
                $index = $this->nameMap[$name];
            }

            $ret[$index] = $value;
        }

        return $ret;
    }

    /**
     * Get datatype for given index
     *
     * @param int $index
     *
     * @return null|string
     */
    public function getTypeAt($index)
    {
        if (isset($this->types[$index])) {
            return $this->types[$index];
        }
    }

    /**
     * Append given bag vlues to this instance
     */
    public function append(ArgumentBag $bag)
    {
        foreach ($bag->data as $index => $value) {
            $this->add($value, $bag->names[$index], $bag->types[$index]);
        }
    }

    /**
     * Append the given array to this instance
     *
     * @param array $array
     */
    public function appendArray(array $array)
    {
        foreach ($array as $index => $value) {
            if (is_int($index)) {
                $this->add($value);
            } else {
                $this->add($value, $index);
            }
        }
    }
}
