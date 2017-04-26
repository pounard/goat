<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Converter\ConverterAwareInterface;
use Goat\Query\Statement;

/**
 * SQL formatter
 */
interface FormatterInterface extends ConverterAwareInterface
{
    /**
     * Format the query
     *
     * @param Statement $query
     *
     * @return string
     */
    public function format(Statement $query) : string;

    /**
     * Format query with parameters and associated casts
     *
     * This is the same as format() will do, but adds type casting around
     * parameters, and rewrite parameters for the targetted formatter.
     *
     * @param string|Statement $query
     * @param mixed[] $parameters
     *
     * @return FormattedQuery
     */
    public function prepare($query, array $parameters = null) : FormattedQuery;
}
