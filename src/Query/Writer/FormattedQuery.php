<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Error\QueryError;

/**
 * Carries a formatted query for a driver, along with its parameters
 */
final class FormattedQuery
{
    private $query;
    private $parameters;

    /**
     * Default constructor
     *
     * @param string $query
     * @param string[] $parameters
     */
    public function __construct(string $query, array $parameters)
    {
        $this->query = $query;
        $this->parameters = $parameters;

        \array_walk($parameters, function ($value, $key) {
            if (null !== $value && !\is_scalar($value)) {
                throw new QueryError(\sprintf("parameter '%s' must be a string, '%s' given", $key, \gettype($value)));
            }
        });
    }

    /**
     * Get formatted query
     *
     * @return string
     */
    public function getQuery() : string
    {
        return $this->query;
    }

    /**
     * Get prepared paramaters
     *
     * @return string[]
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }
}
