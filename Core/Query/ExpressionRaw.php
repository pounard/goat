<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;

/**
 * Represents a raw SQL expression, remember that an expression must always
 * return a single value, in opposition to statements.
 */
class ExpressionRaw implements Expression
{
    private $expression;
    private $arguments;

    /**
     * Default constructor
     *
     * @param string $expression
     *   Raw SQL expression string
     * @param mixed|array $arguments
     *   Key/value pairs or argument list, anonymous and named parameters
     *   cannot be mixed up within the same query
     */
    public function __construct($expression, $arguments = [])
    {
        if (!is_array($arguments)) {
            $arguments = [$arguments];
        }

        $this->expression = $expression;
        $this->arguments = new ArgumentBag();
        $this->arguments->appendArray($arguments);
    }

    /**
     * Get raw SQL string
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->arguments = clone $this->arguments;
    }
}
