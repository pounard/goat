<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareTrait;
use Goat\Core\Error\QueryError;
use Goat\Core\Query\Query;

abstract class AbstractConnection implements ConnectionInterface
{
    use ConverterAwareTrait;

    /**
     * Write cast clause
     *
     * Use '?' for the value placeholder, and '%s' as the type
     *
     * @param string $type
     *
     * @return string
     */
    protected function writeCast($type)
    {
        // This is supposedly SQL-92 standard compliant
        return "cast(? as %s)";
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCastType($type)
    {
        return $type;
    }

    /**
     * Converts all PostgreSQL compatible "$*::TYPE" identifiers in the query
     *
     * This is necessary because we need PDOStatement to known about column
     * types when data is returned from the query, this is a bit ugly, but it
     * will allow the users to specify the datatype they'd like in return
     *
     * @param string $rawSQL
     *   Bare SQL
     * @param mixed[] $parameters
     *   Parameters array to be converted
     *
     * @return array
     *   First value is the query string, second is the reworked array
     *   of parameters, if conversions were needed
     */
    private function rewriteQueryAndParameters($rawSQL, array $parameters)
    {
        if (!$parameters) {
            return [$rawSQL, []];
        }

        $index      = 0;
        $parameters = array_values($parameters);

        $rawSQL = preg_replace_callback('/\$(\*|\d+)(?:::([\w\."]+(?:\[\])?)|)?/', function ($matches) use (&$parameters, &$index) {
            $token = '?';

            if (!array_key_exists($index, $parameters)) {
                throw new QueryError(sprintf("Invalid parameter number bound"));
            }

            if (isset($matches[2])) { // Do we have a type?
                $type = $matches[2];

                $replacement = $parameters[$index];
                $replacement = $this->converter->extract($type, $replacement);

                if ($this->converter->needsCast($type)) {

                    $castAs = $this->converter->cast($type);
                    if (!$castAs) {
                        $castAs = $type;
                    }

                    $token = sprintf($this->writeCast($type), $this->getCastType($castAs));
                }

                $parameters[$index] = $replacement;
            }

            ++$index;

            return $token;

        }, $rawSQL);

        return [$rawSQL, $parameters];
    }

    /**
     * Return the proper SQL and set of parameters
     *
     * @param string|Query $input
     * @param mixed[] $parameters
     *
     * @return array
     *   First value is the query string, second is the reworked array
     *   of parameters, if conversions were needed
     */
    final protected function getProperSql($input, array $parameters = [])
    {
        if (!is_string($input)) {

            if (!$input instanceof Query) {
                throw new QueryError(sprintf("query must be a bare string or an instance of %s", Query::class));
            }

            if (empty($parameters)) {
                $parameters = $input->getArguments();
            }

            $input = $this->getSqlFormatter()->format($input);
        }

        return $this->rewriteQueryAndParameters($input, $parameters);
    }
}
