<?php

namespace Momm\Core\Client\PDO;

use Momm\Core\Client\ResultIteratorInterface;
use Momm\Core\Client\ResultIteratorTrait;

class PDOResultIterator implements ResultIteratorInterface
{
    use ResultIteratorTrait;

    protected $statement;
    protected $columnCount = 0;
    protected $columnNameMap = [];
    protected $columnTypeMap = [];
    protected $useConverter = false;

    /**
     * Default constructor
     *
     * @param \PDOStatement $statement
     * @param boolean $useConverter
     */
    public function __construct(\PDOStatement $statement, $useConverter = true)
    {
        $this->statement = $statement;
        $this->statement->setFetchMode(\PDO::FETCH_ASSOC);

        $this->useConverter = $useConverter;

        $this->collectMetaData();
    }

    /**
     * From metadata-given type, get a valid type name
     *
     * @param string $nativeType
     *
     * @return string
     */
    protected function parseType($nativeType)
    {
        switch ($nativeType) {

            case 'VAR_STRING':
                return 'varchar';

            case 'STRING':
                return 'varchar';

            case 'BLOB':
                return 'bytea';

            case 'LONGLONG':
                return 'int8';

            case 'LONG':
                return 'int4';

            case 'SHORT':
                return 'int2';

            case 'DATETIME':
                return 'timestamp';

            case 'DATE':
                return 'date';

            case 'DOUBLE':
                return 'float8';

            case 'TIMESTAMP':
                return 'timestamp';

            case 'TIME':
                return 'time';

            default:
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
            if ($this->useConverter) {
                yield $this->hydrate($row);
            } else {
                yield $row;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countFields()
    {
        return $this->columnCount;
    }

    /**
     * {@inheritdoc}
     */
    public function countRows()
    {
        return $this->statement->rowCount();
    }

    /**
     * Does this field exists
     *
     * @param string $name
     *
     * @return boolean
     */
    public function fieldExists($name)
    {
        return isset($this->columnNameMap[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldNames()
    {
        return array_flip($this->columnNameMap);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType($name)
    {
        if (isset($this->columnTypeMap[$name])) {
            return $this->columnTypeMap[$name];
        }

        throw new \OutOfBoundsException(sprintf("column '%s' does not exist", $name));
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldName($index)
    {
        if (!is_int($index)) {
            throw new \InvalidArgumentException(sprintf("'%s' is not an integer.\n", $index));
        }

        $pos = array_search($index, $this->columnNameMap);
        if (false !== $pos) {
            return $pos;
        }

        throw new \OutOfBoundsException(sprintf("column %d is out of bounds", $index));
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldNumber($name)
    {
        if (isset($this->columnNameMap[$name])) {
            return $this->columnNameMap[$name];
        }

        throw new \OutOfBoundsException(sprintf("column '%s' does not exist", $name));
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name)
    {
        if (!is_int($name)) {
            $name = $this->getFieldNumber($name);
        }

        $this->statement->fetchColumn($name);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchRowAt($index)
    {
        $values = $this->statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, $index);

        if ($values === false) {
            throw new \OutOfBoundsException(sprintf("Cannot jump to non existing row %d.", $index));
        }

        if ($this->useConverter) {
            return $this->hydrate($values);
        } else {
            return $values;
        }
    }
}
