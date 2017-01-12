<?php

namespace Goat\Driver\PDO;

use Goat\Core\Client\AbstractResultIterator;
use Goat\Core\Error\InvalidDataAccessError;

class DefaultResultIterator extends AbstractResultIterator
{
    protected $statement;
    protected $columnCount = 0;
    protected $columnNameMap = [];
    protected $columnTypeMap = [];

    /**
     * Default constructor
     *
     * @param \PDOStatement $statement
     */
    public function __construct(\PDOStatement $statement)
    {
        $this->statement = $statement;
        $this->statement->setFetchMode(\PDO::FETCH_ASSOC);

        $this->collectMetaData();
    }

    /**
     * From metadata-given type, get a valid type name
     *
     * @param string $nativeType
     *
     * @return string
     */
    protected function parseType($nativeType) : string
    {
        $nativeType = strtolower($nativeType);

        switch (strtolower($nativeType)) {

            case 'string':
            case 'var_string':
            case 'varchar':
            case 'char':
            case 'character':
                return 'varchar';

            case 'blob':
            case 'bytea':
                return 'bytea';

            case 'int8':
            case 'longlong':
                return 'int8';

            case 'int4':
            case 'long':
                return 'int4';

            case 'short':
                return 'int4';

            case 'datetime':
            case 'timestamp':
                return 'timestamp';

            case 'time':
                return 'time';

            case 'date':
                return 'date';

            case 'float4':
                return 'float4';

            case 'double':
            case 'float8':
                return 'float8';

            default:
                if ($this->converter->typeExists($nativeType)) {
                    return $nativeType;
                }

                trigger_error(sprintf("'%s': unknown type", $nativeType));
                return 'unknown';
        }
    }

    /**
     * Collect data types and other data from current statement
     */
    protected function collectMetaData()
    {
        $this->columnCount = $this->statement->columnCount();

        for ($i = 0; $i < $this->columnCount; ++$i) {

            $meta = $this->statement->getColumnMeta($i);
            $key = $meta['name'];

            if (is_numeric($key)) {
                $key = $i;
            }

            $this->columnNameMap[$key] = $i;
            $this->columnTypeMap[$key] = $this->parseType($meta['native_type']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->statement as $row) {
            yield $this->hydrate($row);
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
        return $this->statement->rowCount();
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
        if (is_int($name)) {
            $name = $this->getColumnName($name);
        }

        $ret = [];

        foreach ($this as $row) {
            $ret[] = $row[$name];
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        $row = $this->statement->fetch();

        if ($row) {
            return $this->hydrate($row);
        }
    }
}
