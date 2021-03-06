<?php

declare(strict_types=1);

namespace Goat\Driver\ExtPgSQL;

use Goat\Error\InvalidDataAccessError;
use Goat\Runner\AbstractResultIterator;

class ExtPgSQLResultIterator extends AbstractResultIterator
{
    use ExtPgSQLErrorTrait;

    protected $resource;
    protected $columnCount = 0;
    protected $columnNameMap = [];
    protected $columnTypeMap = [];

    /**
     * Default constructor
     *
     * @param resource $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;

        $this->collectMetaData();
    }

    /**
     * Collect data types and column information
     */
    protected function collectMetaData()
    {
        $this->columnCount = \pg_num_fields($this->resource);
        if (false === $this->columnCount) {
            $this->resultError($this->resource);
        }

        for ($i = 0; $i < $this->columnCount; ++$i) {

            $type = \pg_field_type($this->resource, $i);
            if (false === $type) {
                $this->resultError($this->resource);
            }

            $key = \pg_field_name($this->resource, $i);
            if (false === $type) {
                $this->resultError($this->resource);
            }

            $this->columnNameMap[$key] = $i;
            $this->columnTypeMap[$key] = $type;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        while ($row = \pg_fetch_assoc($this->resource)) {
            if ($this->columnKey) {
                yield $row[$this->columnKey] => $this->hydrate($row);
            } else {
                yield $this->hydrate($row);
            }
        }

        if (false === $row) {
            $this->resultError($this->resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countColumns() : int
    {
        return $this->columnCount;
    }

    /**
     * {@inheritdoc}
     */
    public function countRows() : int
    {
        $ret = \pg_num_rows($this->resource);

        if (-1 === $ret) {
            $this->resultError($this->resource);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists(string $name) : bool
    {
        return isset($this->columnNameMap[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames() : array
    {
        return \array_flip($this->columnNameMap);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(string $name) : string
    {
        if (isset($this->columnTypeMap[$name])) {
            return $this->columnTypeMap[$name];
        }

        throw new InvalidDataAccessError(\sprintf("column '%s' does not exist", $name));
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index) : string
    {
        if (!\is_int($index)) {
            throw new InvalidDataAccessError(\sprintf("'%s' is not an integer.\n", $index));
        }

        $pos = \array_search($index, $this->columnNameMap);
        if (false !== $pos) {
            return $pos;
        }

        throw new InvalidDataAccessError(\sprintf("column %d is out of bounds", $index));
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnNumber(string $name) : int
    {
        if (isset($this->columnNameMap[$name])) {
            return $this->columnNameMap[$name];
        }

        throw new InvalidDataAccessError(\sprintf("column '%s' does not exist", $name));
    }

    /**
     * fetchColumn() implementation that returns a keyed array
     *
     * @return array
     */
    protected function fetchColumnWithKey(string $name, int $index, string $columnKeyName)
    {
        $keyIndex = $this->getColumnNumber($columnKeyName);

        $valueColumn = \pg_fetch_all_columns($this->resource, $index);
        if (false === $valueColumn) {
            throw new InvalidDataAccessError(\sprintf("column '%d' is out of scope of the current result", $index));
        }

        $indexColumn = \pg_fetch_all_columns($this->resource, $keyIndex);
        if (false === $indexColumn) {
            throw new InvalidDataAccessError(\sprintf("column '%d' is out of scope of the current result", $keyIndex));
        }

        $ret = [];

        foreach ($valueColumn as $index => $value) {
            $ret[$indexColumn[$index]] = $this->convertValue($name, $value);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     *
     * @todo could this be improved if fetchColumn() returned an iterator instead of an array?
     */
    public function fetchColumn($name = 0)
    {
        if (\is_string($name)) {
            $index = $this->getColumnNumber($name);
        } else {
            $index = (int)$name;
            $name = $this->getColumnName($index);
        }

        if ($this->columnKey) {
            return $this->fetchColumnWithKey($name, $index, $this->columnKey);
        }

        $ret = [];

        $columns = \pg_fetch_all_columns($this->resource, $index);
        if (false === $columns) {
            throw new InvalidDataAccessError(\sprintf("column '%d' is out of scope of the current result", $index));
        }

        foreach ($columns as $value) {
            $ret[] = $this->convertValue($name, $value);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        $row = \pg_fetch_assoc($this->resource);

        if (false === $row) {
            $this->resultError($this->resource);
        }

        if ($row) {
            return $this->hydrate($row);
        }
    }
}
