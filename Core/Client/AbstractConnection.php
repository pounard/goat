<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareTrait;
use Goat\Core\Error\QueryError;
use Goat\Core\Query\Query;

abstract class AbstractConnection implements ConnectionInterface
{
    use ConverterAwareTrait;

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
     * Write cast clause
     *
     * @param string $placeholder
     *   Placeholder for the value
     * @param string $type
     *   SQL datatype
     *
     * @return string
     */
    protected function writeCast($placeholder, $type)
    {
        // This is supposedly SQL-92 standard compliant
        return sprintf("cast(%s as %s)", $placeholder, $type);
    }

    /**
     * Get the default anonymous placeholder for queries
     *
     * @param int $index
     *   The numerical index position of the placeholder value
     */
    protected function getPlaceholder($index)
    {
        return '?'; // This works for PDO, for example
    }

    /**
     * Converts all typed placeholders in the query and replace them with the
     * correct CAST syntax, this will also convert the argument values if
     * necessary along the way
     *
     * Matches the following things ANYTHING::TYPE where anything can be pretty
     * much anything except for a few SQL control chars, this will make the SQL
     * query writing very much easier for you.
     *
     * Please note that if a the same ANYTHING identifier is specified more than
     * once in the arguments array, with conflicting types specified, only the
     * first being found will do something.
     *
     * And finally, all found placeholders will be replaced by something we can
     * then match once again for placeholder rewrite.
     *
     * This allows the users to specify which type they want to send for each
     * one of their arguments, and sus allows advanced parameter conversion
     * such as:
     *
     *   - \DateTimeInterface objects to either date, time or timestamp
     *   - int to float, float to int, string to any numerical value
     *   - any user defined advanced PHP structure to something the database
     *     will understand in the end
     *
     * Once explicit cast conversion is done, it will attempt an automatic
     * replacement for all remaining values.
     *
     * @param string $rawSQL
     *   Bare SQL
     * @param ArgumentBag $parameters
     *   Parameters array to be converted
     *
     * @return array
     *   First value is the query string, second is the reworked array
     *   of parameters, if conversions were needed
     */
    private function rewriteQueryAndParameters($rawSQL, ArgumentBag $arguments)
    {
        $index      = 0;
        $parameters = $arguments->getAll();
        $done       = [];

        $rawSQL = preg_replace_callback('/\$(\*|\d+)(?:::([\w\."]+(?:\[\])?)|)?/', function ($matches) use (&$parameters, &$index, &$done) {

            $placeholder = $this->getPlaceholder($index);

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

                    $placeholder = $this->writeCast($placeholder, $this->getCastType($castAs));
                }

                $parameters[$index] = $replacement;
                $done[$index] = true;
            }

            ++$index;

            return $placeholder;

        }, $rawSQL);

        // Some parameters might remain untouched, case in which we do need to
        // automatically convert them to something the SQL backend will
        // understand; for example a non explicitely casted \DateTime object
        // into the query will end up as a \DateTime object and the query
        // will fail.
        if (count($done) !== count($parameters)) {
            foreach (array_diff_key($parameters, $done) as $index => $value) {
                $parameters[$index] = $this->converter->guess($value);
            }
        }

        return [$rawSQL, $parameters];
    }

    /**
     * Return the proper SQL and set of parameters
     *
     * @param string|Query $input
     * @param mixed[]|ArgumentBag $parameters
     *
     * @return array
     *   First value is the query string, second is the reworked array
     *   of parameters, if conversions were needed
     */
    final protected function getProperSql($input, $parameters = null)
    {
        $arguments = null;

        if (!is_string($input)) {

            if (!$input instanceof Query) {
                throw new QueryError(sprintf("query must be a bare string or an instance of %s", Query::class));
            }

            if (!$parameters) {
                $arguments = $input->getArguments();
            }

            $input = $this->getSqlFormatter()->format($input);
        }

        if (!$arguments) {
            if ($parameters instanceof ArgumentBag) {
                $arguments = $parameters;
            } else {
                $arguments = new ArgumentBag();

                if ($parameters) {
                    $arguments->appendArray($parameters);
                }
            }
        }

        return $this->rewriteQueryAndParameters($input, $arguments);
    }
}
