<?php

namespace Momm\Core\Client;

use Momm\Core\Converter\ConverterAwareTrait;

trait ConnectionTrait
{
    use ConverterAwareTrait;

    /**
     * Matches PostgreSQL compatible "$*::TYPE" identifiers in the query and
     * return the list
     *
     * @param string $sql
     *   Bare SQL
     *
     * @return string[]
     *   Keys are the found identifier within the query, values are matched
     *   data types
     */
    protected function getParametersType($sql)
    {
        $matches = [];
        preg_match_all('/\$(\*|\d+)(?:::([\w\."]+(?:\[\])?)|)?/', $sql, $matches);

        $ret = [];
        foreach ($matches[0] as $i => $identifier) {
            if ($matches[2]) {
                $ret[$identifier] = str_replace('"', '', $matches[2][$i]);
            } else {
                $ret[$identifier] = null;
            }
        }

        return $ret;
    }

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
        $map = $this->getParametersType($sql);

        if (count($map) !== count($parameters)) {
            throw new \InvalidArgumentException("parameter count does not match query");
        }

        $replacements = [];

        // This is necessary to be able to proceed in order
        $parameters = array_values($parameters);

        $index = 0;
        foreach ($map as $original => $type) {

            // PDO original placeholder
            $replacement = '?';

            if ($type) {
                $parameters[$index] = $this->converter->extract($type, $parameters[$index]);

                if ($this->converter->needsCast($type)) {
                    $replacement = sprintf("cast(? as %s)", $this->converter->cast($type));
                }
            }

            $replacements[$original] = $replacement;
            $index++;
        }

        return [strtr($sql, $replacements), $parameters];
    }

    protected function hydrate($value, $type)
    {
        return $value;
    }
}
