<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;
use Goat\Core\Error\QueryError;

/**
 * Where represents the selection of the SQL query
 */
class Where
{
    use WhereTrait;

    const ARBITRARY = 'statement';

    const AND_STATEMENT = 'and';
    const OR_STATEMENT = 'or';

    const BETWEEN = 'between';
    const NOT_BETWEEN = 'not between';

    const EQUAL = '=';
    const NOT_EQUAL = '<>';

    const GREATER = '>';
    const GREATER_OR_EQUAL = '>=';
    const LESS = '<';
    const LESS_OR_EQUAL = '<=';

    const IN = 'in';
    const NOT_IN = 'not in';

    const IS_NULL = 'is null';
    const NOT_IS_NULL = 'is not null';

    const LIKE = 'like';
    const NOT_LIKE = 'not like';

    /**
     * @var ArgumentBag
     */
    protected $arguments;

    /**
     * @var string
     */
    protected $operator = self::AND_STATEMENT;

    /**
     * @var Where
     */
    protected $parent;

    /**
     * @var mixed[]
     */
    protected $conditions = [];

    /**
     * Default constructor
     *
     * @param string $operator
     *   Where::AND_STATEMENT or Where::OR_STATEMENT, determine which will be
     *   the operator inside this statement
     */
    public function __construct($operator = self::AND_STATEMENT)
    {
        $this->operator = $operator;
    }

    /**
     * Is this statement empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->conditions);
    }

    /**
     * For internal use only
     *
     * @param Where $parent
     *
     * @return $this
     */
    protected function setParent(Where $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Reset internal cache if necessary
     */
    protected function reset()
    {
        $this->arguments = null;
    }

    /**
     * Add a condition
     *
     * @param string $column
     * @param mixed $value
     * @param string $operator
     *
     * @return $this
     */
    public function condition($column, $value, $operator = self::EQUAL)
    {
        if (self::EQUAL === $operator) {
            if (is_array($value)) {
                $operator = self::IN;
            }
        } else if (self::NOT_EQUAL === $operator) {
            if (is_array($value)) {
                $operator = self::NOT_IN;
            }
        } else if (self::BETWEEN === $operator || self::NOT_BETWEEN === $operator) {
            if (!is_array($value) || 2 !== count($value)) {
                throw new QueryError("between and not between operators needs exactly 2 values");
            }
        }

        $this->conditions[] = [$column, $value, $operator];

        $this->reset();

        return $this;
    }

    /**
     * Add an abitrary statement
     *
     * @param string $statement
     *   SQL string, which may contain parameters
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     *
     * @return $this
     */
    public function statement($statement, $arguments = [])
    {
        $this->condition($statement, $arguments, self::ARBITRARY);

        return $this;
    }

    /**
     * Get a raw SQL string
     *
     * @param string $raw
     * @param mixed[] $arguments
     *
     * @return RawStatement
     */
    public function raw($statement, $arguments = [])
    {
        return new RawStatement($statement, $arguments);
    }

    /**
     * Start a new parenthesis statement
     *
     * @param string $operator
     *   Where::OP_AND or Where::OP_OR, determine which will be the operator
     *   inside this where statement
     *
     * @return $this
     */
    public function open($operator = self::AND_STATEMENT)
    {
        $this->reset();

        return $this->conditions[] = (new Where($operator))->setParent($this);
    }

    /**
     * End a previously started statement
     *
     * @return $this
     */
    public function close()
    {
        if (!$this->parent) {
            throw new QueryError("cannot end a statement without a parent");
        }

        return $this->parent;
    }

    /**
     * Get arguments
     *
     * @return ArgumentBag
     */
    public function getArguments()
    {
        if (null !== $this->arguments) {
            return $this->arguments;
        }

        $arguments = new ArgumentBag();

        foreach ($this->conditions as $condition) {
            if ($condition instanceof Where) {

                if (!$condition->isEmpty()) {
                    $arguments->append($condition->getArguments());
                }

            } else {
                list(, $value, $operator) = $condition;

                if ($value instanceof RawStatement) {
                    $arguments->appendArray($value->getParameters());
                } else {
                    switch ($operator) {

                        case Where::IS_NULL:
                        case Where::NOT_IS_NULL:
                            break;

                        default:
                            if (is_array($value)) {
                                $arguments->appendArray($value);
                            } else {
                                $arguments->add($value);
                            }
                            break;
                    }
                }
            }
        }

        return $this->arguments = $arguments;
    }

    /**
     * Get operator
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Get conditions
     *
     * @return mixed[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->arguments = null;

        foreach ($this->conditions as $index => $condition) {
            if (is_object($condition)) {
                $this->conditions[$index] = clone $condition;
            } else if (is_array($condition)) {
                foreach ($condition as $key => $item) {
                    if (is_object($item)) {
                        $this->conditions[$index][$key] = clone $item;
                    }
                }
            }
        }
    }
}
