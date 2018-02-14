<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Converter\ConverterAwareTrait;
use Goat\Converter\ConverterInterface;
use Goat\Error\QueryError;
use Goat\Query\ArgumentBag;
use Goat\Query\Query;
use Goat\Query\Statement;

/**
 * Basics for for the SQL foramtter.
 */
abstract class FormatterBase implements FormatterInterface
{
    use ConverterAwareTrait;
    use EscaperAwareTrait;

    /**
     * Escape sequence matching magical regex
     */
    const PARAMETER_MATCH = '@
        ESCAPE
        \$+(\*|\d+)                     # Matches any number of dollar signs followed with * or digit
        (?:::([\w\."]+(?:\[\])?)|)?     # Matches valid ::WORD cast
        @x';

    /**
     * Parameters matching regex, built upon known espace sequences
     *
     * @var string
     */
    private $matchParametersRegex;

    /**
     * Default constructor
     *
     * @param EscaperInterface $escaper
     */
    public function __construct(EscaperInterface $escaper)
    {
        $this->setEscaper($escaper);
        $this->buildParameterRegex();
    }

    /**
     * Get the default anonymous placeholder for queries
     *
     * @param int $index
     *   The numerical index position of the placeholder value
     *
     * @return string
     *   The placeholder
     */
    protected function writePlaceholder(int $index) : string
    {
        // We needed a default, this is PDO's and Oracle default
        return '?';
    }

    /**
     * Allows the driver to proceed to different type cast
     *
     * Use this if you want to keep a default implementation for a specific
     * type and don't want to override it.
     *
     * @param string $type
     *   The internal type carried by converters
     *
     * @return string
     *   The real type the server will understand
     */
    protected function getCastType(string $type) : string
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
    protected function writeCast(string $placeholder, string $type) : string
    {
        // This is supposedly SQL-92 standard compliant, but can be overriden
        return sprintf("cast(%s as %s)", $placeholder, $type);
    }

    /**
     * Uses the connection driven escape sequences to build the parameter
     * matching regex.
     */
    final private function buildParameterRegex()
    {
        // Please see this really excellent Stack Overflow answer:
        //   https://stackoverflow.com/a/23589204
        $patterns = [];

        foreach ($this->escaper->getEscapeSequences() as $sequence) {
            $sequence = preg_quote($sequence);
            $patterns[] = sprintf("%s.+%s", $sequence, $sequence);
        }

        if ($patterns) {
            $this->matchParametersRegex = str_replace('ESCAPE', sprintf("(%s)|", implode("|", $patterns)), self::PARAMETER_MATCH);
        } else {
            $this->matchParametersRegex = str_replace('ESCAPE', self::PARAMETER_MATCH);
        }
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
     * @param mixed[] $overrides
     *   Parameters overrides
     *
     * @return array
     *   First value is the query string, second is the reworked array
     *   of parameters, if conversions were needed
     */
    final private function rewriteQueryAndParameters(string $rawSQL, ArgumentBag $arguments, array $overrides = []) : FormattedQuery
    {
        $index      = 0;
        $parameters = $arguments->getAll($overrides);
        $done       = [];

        // See https://stackoverflow.com/a/3735908 for the  starting
        // sequence explaination, the rest should be comprehensible.
        // Working version: '/\$+(\*|\d+)(?:::([\w\."]+(?:\[\])?)|)?/'
        $rawSQL = preg_replace_callback($this->matchParametersRegex, function ($matches) use (&$parameters, &$index, &$done, $arguments) {

            // Still not implemented the (SKIP*)(F*) variant for the regex
            // so I do need to exclude patterns we DO NOT want to match from
            // here.
            if ('$' !== $matches[0][0]) {
                return $matches[0];
            }

            // Consider that $$ is a valid escape sequence, and should not be
            // changed, more generally an even count is a series of escape
            // sequences, whereas having an odd count means that we do have
            // escape sequences and a parameter identifier at the same time.
            // For example:
            //  - $* : parameter
            //  - $$* : escape sequence then *
            //  - $$$* : escape sequence then parameter
            //  - $$$$* : 2 escape sequences then *
            //  - ... and you get it
            $prefix = '';
            if ('$' === $matches[0][1]) {
                // We don't need to check if the second char is not a $ sign
                $count = substr_count($matches[0], '$');
                if (0 === $count % 2) {
                    // Ignore this string, return complete string.
                    return $matches[0];
                } else {
                    $prefix = str_repeat('*', $count - 1);
                }
            }

            $placeholder = $this->writePlaceholder($index);

            if (!array_key_exists($index, $parameters)) {
                throw new QueryError(sprintf("Invalid parameter number bound"));
            }

            if (isset($matches[3])) { // Do we have a type?
                $type = $matches[3];

                $replacement = $parameters[$index];
                $replacement = $this->converter->toSQL($type, $replacement);

                if ($this->converter) {
                    if ($this->converter->needsCast($type)) {
                        $castAs = $this->converter->cast($type);
                        if (!$castAs) {
                            $castAs = $type;
                        }
                        $placeholder = $this->writeCast($placeholder, $this->getCastType($castAs));
                    }
                }

                $parameters[$index] = $replacement;
                $done[$index] = true;
            }

            ++$index;

            return $prefix . $placeholder;
        }, $rawSQL);

        // Some parameters might remain untouched, case in which we do need to
        // automatically convert them to something the SQL backend will
        // understand; for example a non explicitely casted \DateTime object
        // into the query will end up as a \DateTime object and the query
        // will fail.
        if (count($done) !== count($parameters)) {
            foreach (array_diff_key($parameters, $done) as $index => $value) {
                $type = $arguments->getTypeAt($index);
                if ($this->converter) {
                    if (!$type) {
                        $type = ConverterInterface::TYPE_UNKNOWN;
                    }
                    $parameters[$index] = $this->converter->toSQL($type, $value);
                } else {
                    $parameters[$index] = $value;
                }
            }
        }

        return new FormattedQuery($rawSQL, $parameters);
    }

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
    final public function prepare($query, array $parameters = null) : FormattedQuery
    {
        $arguments = null;
        $overrides = [];

        if (!is_string($query)) {
            if (!$query instanceof Statement) {
                throw new QueryError(sprintf("query must be a bare string or an instance of %s", Query::class));
            }

            $arguments = $query->getArguments();
            $query = $this->format($query);
        }

        if (!$arguments) {
            if ($parameters instanceof ArgumentBag) {
                $arguments = $parameters;
            } else {
                $arguments = new ArgumentBag();
                if (is_array($parameters)) {
                    $overrides = $parameters;
                }
            }
        } else if (is_array($parameters)) {
            $overrides = $parameters;
        }

        return $this->rewriteQueryAndParameters($query, $arguments, $overrides);
    }
}
