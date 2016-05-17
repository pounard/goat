<?php

namespace Momm\Core\Query;

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
     * @var mixed[]
     *   Computed arguments
     */
    protected $arguments;

    /**
     * @var string
     *   Computed SQL string
     */
    protected $sql;

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
        if (null !== $this->arguments) {
            $this->arguments = null;
            $this->sql = null;
        }
    }

    /**
     * Create placeholder list for the given arguments
     *
     * This will be used only in order to build 'in' and 'not in' conditions
     *
     * @param mixed[] $arguments
     *   Arbitrary arguments
     * @param string $type = null
     *   Data type of arguments
     *
     * @return string
     */
    protected function createPlaceholders($arguments, $type = '')
    {
        return implode(', ', array_map(function () { return '$*'; }, $arguments));
    }

    /**
     * Merge given arguments into current argument array
     *
     * @param mixed $arguments
     *   Can be either a single value or an array of values
     */
    protected function mergeArguments($arguments)
    {
        if (is_array($arguments)) {
            foreach ($arguments as $argument) {
                $this->arguments[] = $argument;
            }
        } else {
            $this->arguments[] = $arguments;
        }
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
                throw new \InvalidArgumentException("between and not between operators needs exactly 2 values");
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
            throw new \LogicException("cannot end a statement without a parent");
        }

        return $this->parent;
    }

    /**
     * Get ordered arguments for the formatted where statement
     *
     * This method will force the SQL string to be pre-computed and stored
     * internally, later ::__toString() or ::format() calls will return the
     * pre-computed values.
     *
     * @return mixed[]
     */
    public function getArguments()
    {
        if (null === $this->sql) {
            $this->format();
        }

        return $this->arguments;
    }

    /**
     * That's what ::format() really does, and will be used for recursion, we
     * cannot allow sub-statements to be formatted by the end user.
     *
     * @return string
     */
    protected function doFormat()
    {
        $output = [];

        if (null !== $this->sql) {
            return $this->sql;
        }

        $this->arguments = [];

        if ($this->isEmpty()) {
            // Definitely legit
            return '1';
        }

        foreach ($this->conditions as $condition) {
            if ($condition instanceof Where) {

                if (!$condition->isEmpty()) {
                    $output[] = '(' . $condition->doFormat() . ')';
                    $this->mergeArguments($condition->getArguments());
                }

            } else {
                list($column, $value, $operator) = $condition;

                switch ($operator) {

                    case self::ARBITRARY:
                        $output[] = $column;
                        $this->mergeArguments($value);
                        break;

                    case self::IS_NULL:
                    case self::NOT_IS_NULL:
                        $output[] = sprintf('%s %s', $column, $operator);
                        break;

                    case self::IN:
                    case self::NOT_IN:
                        $output[] = sprintf('%s %s (%s)', $column, $operator, $this->createPlaceholders($value));
                        $this->mergeArguments($value);
                        break;

                    case self::BETWEEN:
                    case self::NOT_BETWEEN:
                        $output[] = sprintf('%s %s $* and $*', $column, $operator);
                        $this->mergeArguments($value);
                        break;

                    default:
                        $output[] = sprintf('%s %s $*', $column, $operator);
                        $this->mergeArguments($value);
                        break;
                }
            }
        }

        return $this->sql = implode(' ' . $this->operator . ' ', $output);
    }

    /**
     * Get the formatted where clause
     *
     * It will also build the ordered list of arguments internally and serve
     * as a cache for later faster retrieving. Each new call to this method
     * will return the pre-computed output until a new condition or statement
     * is added.
     *
     * @return string
     */
    public function format()
    {
        return $this->doFormat();
    }

    /**
     * Alias of ::format()
     *
     * @return string
     *
     * @see Where::format()
     */
    public function __toString()
    {
        return $this->format();
    }
}
