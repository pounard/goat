<?php

namespace Momm\Foundation\Session;

use PommProject\Foundation\Session\ResultHandler as PommResultHandler;

class ResultHandler extends PommResultHandler
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

        $this->collectMetaData();
    }

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

            default:
                trigger_error(sprintf("'%s': unknown type", $nativeType));
                return 'unknown';
        }
    }

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
    public function __destruct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function free()
    {
        if ($this->statement) {
            $this->statement->closeCursor();
            unset($this->statement);
        }

        return $this;
    }

    protected function getStatement()
    {
        if (!$this->statement) {
            throw new \LogicException("Cannot run the result handler after freeing it");
        }

        return $this->statement;
    }

    /**
     * fetchRow
     *
     * Fetch a row as associative array. Index starts from 0.
     *
     * @access public
     * @param  int   $index
     * @throws \OutOfBoundsException if $index out of bounds.
     * @return array
     */
    public function fetchRow($index)
    {
        $values = @pg_fetch_assoc($this->handler, $index);

        if ($values === false) {
            throw new \OutOfBoundsException(sprintf("Cannot jump to non existing row %d.", $index));
        }

        return $values;
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
        return $this->getStatement()->rowCount();
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
        return $this->statement->fetchColumn($this->getFieldNumber($name));
    }

    /**
     * {@inheritdoc}
     */
    public function fieldExist($name)
    {
        return isset($this->columnNameMap[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeOid($name)
    {
        throw new \Exception("Not implemented yet");
    }
}
