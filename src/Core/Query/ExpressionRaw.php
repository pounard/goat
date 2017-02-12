<?php

declare(strict_types=1);

namespace Goat\Core\Query;

use Goat\Core\Client\ArgumentBag;

/**
 * Represents a raw SQL expression, remember that an expression must always
 * return a single value, in opposition to statements.
 */
final class ExpressionRaw implements Expression
{
    private $expressionString;
    private $arguments;

    /**
     * Default constructor
     *
     * @param string $expressionString
     *   Raw SQL expression string
     * @param mixed|array $arguments
     *   Key/value pairs or argument list, anonymous and named parameters
     *   cannot be mixed up within the same query
     */
    public function __construct(string $expressionString, $arguments = [])
    {
        if (!is_array($arguments)) {
            $arguments = [$arguments];
        }

        $this->expressionString = $expressionString;
        $this->arguments = new ArgumentBag();
        $this->arguments->appendArray($arguments);
    }

    /**
     * Get raw SQL string
     *
     * @return string
     */
    public function getString() : string
    {
        return $this->expressionString;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
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
