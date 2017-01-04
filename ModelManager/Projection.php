<?php

namespace Goat\ModelManager;

/**
 * Model manager project is based upon an entity structure
 */
class Projection
{
    protected $relationAlias = null;
    protected $fields = [];
    protected $types = [];

    /**
     * Default constructor
     *
     * @param EntityStructure $structure
     * @param string $tableAlias
     */
    public function __construct(EntityStructure $structure = null, $tableAlias = null)
    {
        $this->relationAlias = $tableAlias;

        if ($structure) {

            if ($structure->hasPrimaryKey()) {
                foreach ($structure->getPrimaryKey() as $column) {
                    $this->setField($column);
                }
            }

            foreach ($structure->getDefinition() as $column => $type) {
                $this->setField($column, null, $type);
            }
        }
    }

    /**
     * Set relation alias
     *
     * @param string $relationAlias
     *
     * @return $this
     */
    public function setRelationAlias($relationAlias)
    {
        $this->relationAlias = $relationAlias;

        return $this;
    }

    /**
     * Set or replace a field with a content.
     *
     * @param string $alias
     *   Field name, which also be the alias in the 'select'
     * @param string $statement
     *   It might any of:
     *     - null, case in which the alias will be used as column name
     *     - COLUMN string, case in which it will be automatically tokenized
     *       using "%:COLUMN:%" syntax for later table alias prefixing
     *     - any valid SQL statement, in which you may use any number of
     *       tokenized column names such as "%:COLUMN:%", which will be treated
     *       as column names and later prefixed with table alias
     * @param string $type
     *   Field data type
     *
     * @return $this
     */
    public function setField($alias, $statement = null, $type = null)
    {
        if ($statement === null) {
            $statement = '%:' . $alias . ':%';
        }

        $this->fields[$alias] = $statement;
        $this->types[$alias]  = $type;

        return $this;
    }

    /**
     * Set or override a field type definition.
     *
     * @param string $alias
     *   Field alias
     * @param string $type
     *   Field data type
     *
     * @return $this
     */
    public function setFieldType($alias, $type)
    {
        if (!array_key_exists($alias, $this->fields)) {
            throw new \InvalidArgumentException(sprintf("field alias '%s' is not defined in projection", $alias));
        }

        $this->types[$alias] = $type;

        return $this;
    }

    /**
     * Remove field from projection
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeField($alias)
    {
        unset($this->fields[$alias], $this->types[$alias]);

        return $this;
    }

    /**
     * Does this project have the given field
     *
     * @param string $name
     *
     * @return boolean
     */
    public function hasField($alias)
    {
        return isset($this->fields[$alias]);
    }

    /**
     * Get the field type
     *
     * @param string $name
     *
     * @return string
     *   May be null if not data type is set for the given field
     */
    public function getFieldType($alias)
    {
        if (!array_key_exists($alias, $this->fields)) {
            throw new \InvalidArgumentException(sprintf("field alias '%s' is not defined in projection", $alias));
        }

        return $this->types[$alias];
    }

    /**
     * Get the field aliases list
     *
     * @return string[]
     */
    public function getFieldNames()
    {
        return array_keys($this->fields);
    }

    /**
     * Get the field types
     *
     * @return string[]
     *   Keys are field aliases, values are data types, which can be null if
     *   not specified
     */
    public function getFieldTypes()
    {
        return $this->types;
    }

    /**
     * Format fields for 'select'
     *
     * @return string
     */
    public function format()
    {
        $output = [];

        foreach ($this->fields as $alias => $statement) {
            $statement = $this->replaceToken($statement, $this->relationAlias);
            $output[] = $statement . ' as ' . $alias;
        }

        return implode(', ', $output);
    }

    /**
     * Format fields for 'select'
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format();
    }

    /**
     * Replace placeholders with their quoted names
     *
     * @param string $string
     *   Arbitrary field statement
     * @param string $relationAlias
     *   Table alias if any
     *
     * @return string
     */
    protected function replaceToken($string, $relationAlias)
    {
        return preg_replace_callback(
            '/%:(\w.*):%/U',
            function (array $matches) use ($relationAlias) {
                // @todo remove those addcslashes, it must happen via ConnectionInterface::escapeIdentifier()
                if ($relationAlias) {
                    return sprintf('%s.%s', $relationAlias, addcslashes($matches[1], '"\\'));
                } else {
                    return sprintf('%s', addcslashes($matches[1], '"\\'));
                }
            },
            $string
        );
    }
}
