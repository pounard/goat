<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareTrait;

trait ConnectionTrait
{
    use ConverterAwareTrait;

    /**
     * Converts all PostgreSQL compatible "$*::TYPE" identifiers in the query
     *
     * This is necessary because we need PDOStatement to known about column
     * types when data is returned from the query, this is a bit ugly, but it
     * will allow the users to specify the datatype they'd like in return
     *
     * @param string $sql
     *   Bare SQL
     * @param mixed[] $parameters
     *   Parameters array to be converted
     *
     * @return string
     *   Rewritten query
     */
    protected function rewriteQueryAndParameters($sql, array $parameters)
    {
        if (!$parameters) {
            return [$sql, []];
        }

        $index      = 0;
        $parameters = array_values($parameters);

        $sql = preg_replace_callback('/\$(\*|\d+)(?:::([\w\."]+(?:\[\])?)|)?/', function ($matches) use (&$parameters, &$index) {
            $token = '?';

            if (!array_key_exists($index, $parameters)) {
                throw new \InvalidArgumentException(sprintf("Invalid parameter number bound"));
            }

            if (isset($matches[2])) { // Do we have a type?
                $type = $matches[2];

                $replacement = $parameters[$index];
                $replacement = $this->converter->extract($type, $replacement);

                if ($this->converter->needsCast($type)) {
                    $token = sprintf("cast(? as %s)", $this->converter->cast($type));
                }

                $parameters[$index] = $replacement;
            }

            ++$index;

            return $token;

        }, $sql);

        return [$sql, $parameters];
    }

    protected function hydrate($value, $type)
    {
        return $value;
    }
}
