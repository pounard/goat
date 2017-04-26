<?php

declare(strict_types=1);

namespace Goat\Driver\PgSQL;

use Goat\Core\Error\InvalidDataAccessError;
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
        $this->columnCount = pg_num_fields($this->resource);
        if (false === $this->columnCount) {
            $this->resultError($this->resource);
        }

        for ($i = 0; $i < $this->columnCount; ++$i) {

            $type = pg_field_type($this->resource, $i);
            if (false === $type) {
                $this->resultError($this->resource);
            }

            $key = pg_field_name($this->resource, $i);
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
        while ($row = pg_fetch_assoc($this->resource)) {
            yield $this->hydrate($row);
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
        $ret = pg_num_rows($this->resource);

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
        return array_flip($this->columnNameMap);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(string $name) : string
    {
        if (isset($this->columnTypeMap[$name])) {
            return $this->columnTypeMap[$name];
        }

        throw new InvalidDataAccessError(sprintf("column '%s' does not exist", $name));
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index) : string
    {
        if (!is_int($index)) {
            throw new InvalidDataAccessError(sprintf("'%s' is not an integer.\n", $index));
        }

        $pos = array_search($index, $this->columnNameMap);
        if (false !== $pos) {
            return $pos;
        }

        throw new InvalidDataAccessError(sprintf("column %d is out of bounds", $index));
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnNumber(string $name) : int
    {
        if (isset($this->columnNameMap[$name])) {
            return $this->columnNameMap[$name];
        }

        throw new InvalidDataAccessError(sprintf("column '%s' does not exist", $name));
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name = 0)
    {
        if (is_string($name)) {
            $index = $this->getColumnNumber($name);
        } else {
            $index = (int)$name;
            $name = $this->getColumnName($index);
        }

        $ret = [];

        $columns = pg_fetch_all_columns($this->resource, $index);
        if (false === $columns) {
            throw new InvalidDataAccessError(sprintf("column '%d' is out of scope of the current result", $index));
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
        $row = pg_fetch_assoc($this->resource);

        if (false === $row) {
            $this->resultError($this->resource);
        }

        if ($row) {
            return $this->hydrate($row);
        }
    }
}
