<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;
use Goat\Core\Client\ArgumentHolderInterface;
use Goat\Core\Error\QueryError;

/**
 * Where represents the selection of the SQL query
 */
final class Where implements Statement
{
    use WhereTrait;

    const AND = 'and';
    const BETWEEN = 'between';
    const EQUAL = '=';
    const EXISTS = 'exists';
    const GREATER = '>';
    const GREATER_OR_EQUAL = '>=';
    const IN = 'in';
    const IS_NULL = 'is null';
    const LESS = '<';
    const LESS_OR_EQUAL = '<=';
    const LIKE = 'like';
    const NOT_BETWEEN = 'not between';
    const NOT_EQUAL = '<>';
    const NOT_EXISTS = 'not exists';
    const NOT_IN = 'not in';
    const NOT_IS_NULL = 'is not null';
    const NOT_LIKE = 'not like';
    const OR = 'or';

    /**
     * @var ArgumentBag
     */
    protected $arguments;

    /**
     * @var string
     */
    protected $operator = self::AND;

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
     *   Where::AND or Where::OR, determine which will be
     *   the operator inside this statement
     */
    public function __construct($operator = self::AND)
    {
        $this->operator = $operator;
    }

    /**
     * Is this statement empty
     *
     * @return bool
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
        // Never use unset() this will unassign the class property and make
        // PHP raise notices on further accesses.
        $this->arguments = null;
    }

    /**
     * Normalize value
     *
     * @param mixed $value
     *
     * @return Statement
     */
    private function normalizeValue($value) : Statement
    {
        if (!$value instanceof Statement) {
            return new ExpressionValue($value);
        }

        return $value;
    }

    /**
     * Normalize column reference
     *
     * @param string|ExpressionColumn $column
     *
     * @return ExpressionColumn
     */
    private function normalizeColumn($column) : ExpressionColumn
    {
        if ($column instanceof ExpressionColumn) {
            return $column;
        }
        if (is_string($column)) {
            return new ExpressionColumn($column);
        }
        throw new QueryError(sprintf("column reference must be a string or an instance of %s", ExpressionColumn::class));
    }

    /**
     * Add a condition
     *
     * @param string|ExpressionColumn $column
     * @param Statement $value
     * @param string $operator
     *
     * @return $this
     */
    public function condition($column, $value, string $operator = self::EQUAL)
    {
        $column = $this->normalizeColumn($column);

        if (is_array($value)) {
            $value = array_map([$this, 'normalizeValue'], $value);
        } else {
            $value = $this->normalizeValue($value);
        }

        if (self::EQUAL === $operator) {
            if (is_array($value) || $value instanceof SelectQuery) {
                $operator = self::IN;
            }
        } else if (self::NOT_EQUAL === $operator) {
            if (is_array($value) || $value instanceof SelectQuery) {
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
     * Add an abitrary SQL expression
     *
     * @param string|Expression $expression
     *   SQL string, which may contain parameters
     * @param mixed|mixed[] $arguments
     *   Parameters for the arbitrary SQL
     *
     * @return $this
     */
    public function expression($expression, $arguments = [])
    {
        if ($expression instanceof Where || $expression instanceof Expression) {
            if ($arguments) {
                throw new QueryError(sprintf("you cannot call %s::expression() and pass arguments if the given expression is not a string", __CLASS__));
            }
        } else {
            if (!is_array($arguments)) {
                $arguments = [$arguments];
            }
            $expression = new ExpressionRaw($expression, $arguments);
        }

        $this->conditions[] = [null, $expression, null];

        return $this;
    }

    /**
     * Add an exists condition
     */
    public function exists(SelectQuery $query)
    {
        $this->conditions[] = [null, $query, self::EXISTS];

        return $this;
    }

    /**
     * Add an exists condition
     */
    public function notExists(SelectQuery $query)
    {
        $this->conditions[] = [null, $query, self::NOT_EXISTS];

        return $this;
    }

    /**
     * Start a new parenthesis statement
     *
     * @param string $operator
     *   Where::OP_AND or Where::OP_OR, determine which will be the operator
     *   inside this where statement
     *
     * @return Where
     */
    public function open(string $operator = self::AND) : Where
    {
        $this->reset();

        $where = (new Where($operator))->setParent($this);
        $this->conditions[] = [null, $where, null];

        return $where;
    }

    /**
     * End a previously started statement
     *
     * @return Where
     */
    public function close() : Where
    {
        if (!$this->parent) {
            throw new QueryError("cannot end a statement without a parent");
        }

        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        if (null !== $this->arguments) {
            return $this->arguments;
        }

        $arguments = new ArgumentBag();

        foreach ($this->conditions as $condition) {
            list(, $value, $operator) = $condition;

            if ($value instanceof ArgumentHolderInterface) {
                $arguments->append($value->getArguments());
            } else {
                switch ($operator) {

                    case Where::IS_NULL:
                    case Where::NOT_IS_NULL:
                        break;

                    default:
                        // This is ugly as hell, fix me.
                        if (!is_array($value)) {
                            $value = [$value];
                        }
                        foreach ($value as $candidate) {
                            if ($candidate instanceof ArgumentHolderInterface) {
                                $arguments->append($candidate->getArguments());
                            } else {
                                $arguments->add($candidate);
                            }
                        }
                        break;
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
    public function getOperator() : string
    {
        return $this->operator;
    }

    /**
     * Get conditions
     *
     * @return mixed[]
     */
    public function getConditions() : array
    {
        return $this->conditions;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->reset();

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
