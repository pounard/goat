<?php

namespace Goat\Core\Client;

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
        $this->nameMap[$name] = $index;
    }

    /**
     * Get all parameters
     *
     * @return $data
     */
    public function getAll()
    {
        return $this->data;
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
