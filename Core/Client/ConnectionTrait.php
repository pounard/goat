<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareTrait;

trait ConnectionTrait /* implements ConnectionInterface */
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

        }, $sql);

        return [$sql, $parameters];
    }

    protected function hydrate($value, $type)
    {
        return $value;
    }
}
