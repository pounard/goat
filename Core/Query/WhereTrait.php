<?php

namespace Goat\Core\Query;

/**
 * Convenience functions have been separated for easier Where class readability
 */
trait WhereTrait
{
    /**
     * Add a condition
     *
     * @param string $column
     * @param mixed $value
     * @param string $operator
     *
     * @return $this
     */
    abstract public function condition($column, $value, $operator = Where::EQUAL);

    /**
     * Start a new parenthesis statement
     *
     * @param string $operator
     *   Where::AND or Where::OR, determine which will be the operator
     *   inside this where statement
     *
     * @return $this
     */
    abstract public function open($operator = Where::AND);

    /**
     * End a previously started statement
     *
     * @return $this
     */
    abstract public function close();

    /**
     * '=' condition
     *
     * If value is an array, this will be converted to a 'in' condition
     *
     * @param string $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isEqual($column, $value)
    {
        return $this->condition($column, $value, Where::EQUAL);
    }

    /**
     * '<>' condition
     *
     * If value is an array, this will be converted to a 'not in' condition
     *
     * @param string $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isNotEqual($column, $value)
    {
        return $this->condition($column, $value, Where::NOT_EQUAL);
    }

    /**
     * 'in' condition
     *
     * @param string $column
     * @param mixed[] $values
     *
     * @return $this
     */
    public function isIn($column, $values)
    {
        return $this->condition($column, $values, Where::IN);
    }

    /**
     * 'not in' condition
     *
     * @param string $column
     * @param mixed[] $values
     *
     * @return $this
     */
    public function isNotIn($column, $values)
    {
        return $this->condition($column, $values, Where::NOT_IN);
    }

    /**
     * '>' condition
     *
     * @param string $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isGreater($column, $value)
    {
        return $this->condition($column, $value, Where::GREATER);
    }

    /**
     * '<' condition
     *
     * @param string $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isLess($column, $value)
    {
        return $this->condition($column, $value, Where::LESS);
    }

    /**
     * '>=' condition
     *
     * @param string $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isGreaterOrEqual($column, $value)
    {
        return $this->condition($column, $value, Where::GREATER_OR_EQUAL);
    }

    /**
     * '<=' condition
     *
     * @param string $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isLessOrEqual($column, $value)
    {
        return $this->condition($column, $value, Where::LESS_OR_EQUAL);
    }

    /**
     * 'between' condition
     *
     * @param string $column
     * @param mixed $from
     * @param mixed $to
     *
     * @return $this
     */
    public function isBetween($colunm, $from, $to)
    {
        return $this->condition($colunm, [$from, $to], Where::BETWEEN);
    }

    /**
     * 'not between' condition
     *
     * @param string $column
     * @param mixed $from
     * @param mixed $to
     *
     * @return $this
     */
    public function isNotBetween($colunm, $from, $to)
    {
        return $this->condition($colunm, [$from, $to], Where::NOT_BETWEEN);
    }

    /**
     * Add an is null condition
     *
     * @param string $column
     *
     * @return $this
     */
    public function isNull($column)
    {
        return $this->condition($column, null, Where::IS_NULL);
    }

    /**
     * Add an is not null condition
     *
     * @param string $column
     *
     * @return $this
     */
    public function isNotNull($column)
    {
        return $this->condition($column, null, Where::NOT_IS_NULL);
    }

    /**
     * Open an and clause
     */
    public function and()
    {
        return $this->open(Where::AND);
    }

    /**
     * Open an or clause
     */
    public function or()
    {
        return $this->open(Where::OR);
    }

    /**
     * Alias of ::close()
     */
    public function end()
    {
        return $this->close();
    }
}
