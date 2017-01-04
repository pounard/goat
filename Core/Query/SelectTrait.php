<?php

namespace Goat\Core\Query;

trait SelectTrait
{
    private $fields = [];
    private $fieldsUnaliased = [];

    /**
     * Set or replace a field with a content.
     *
     * @param string $statement
     *   SQL select field
     * @param string
     *   If alias to be different from the field
     *
     * @return $this
     */
    public function field($statement, $alias = null)
    {
        $noAlias = false;

        if (!$alias) {
            if (!is_string($statement)) {
                throw new \InvalidArgumentException("when providing no alias for select field, statement must be a string");
            }

            // Match for RELATION.COLUMN for aliasing properly
            if (false !==  strpos($statement, '.')) {
                list(, $column) = explode('.', $statement);

                if ('*' === $column) {
                    $alias = $statement;
                    $noAlias = true;
                } else {
                    $alias = $column;
                }

            } else {
                $alias = $statement;
            }
        }

        $this->fieldsUnaliased[$alias] = $noAlias;
        $this->fields[$alias] = $statement;

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
        unset($this->fields[$alias], $this->fieldsUnaliased[$alias]);

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

    protected function formatProjection($statement, $alias = null)
    {
        if ($alias) {
            return $statement . ' as ' . $alias;
        }
        return $statement;
    }

    /**
     * Format fields for 'select'
     *
     * @return string
     */
    private function formatProjectionAll()
    {
        if (!$this->fields) {
            return '*';
        }

        $output = [];

        foreach ($this->fields as $alias => $statement) {
            if ($this->fieldsUnaliased[$alias]) {
                $output[] = $this->formatProjection($statement);
            } else {
                $output[] = $this->formatProjection($statement, $alias);
            }
        }

        return implode(', ', $output);
    }
}
