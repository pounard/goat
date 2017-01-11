<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareTrait;
use Goat\Core\DebuggableTrait;
use Goat\Core\Error\QueryError;
use Goat\Core\Error\TransactionError;
use Goat\Core\Query\DeleteQuery;
use Goat\Core\Query\InsertQueryQuery;
use Goat\Core\Query\InsertValuesQuery;
use Goat\Core\Query\Query;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\UpdateQuery;
use Goat\Core\Transaction\Transaction;
use Goat\Core\Error\GoatError;

/**
 * Default implementation for connection, it handles for you:
 *
 *  - transaction handling, with security check for not creating a transaction
 *    twice at the same time; it uses weak references if the PHP weakref
 *    extension is enabled;
 *
 *  - query builders creation, you don't need to override any of this except for
 *    very peculiar drivers;
 *
 *  - query parameters rewriting and conversion, this is a tricky one but it's
 *    thoroughly tested: you should not rewrite this by yourself.
 */
abstract class AbstractConnection implements ConnectionInterface
{
    use ConverterAwareTrait;
    use DebuggableTrait;

    private $currentTransaction;

    /**
     * {@inheritdoc}
     */
    public function supportsReturning()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints()
    {
        return true;
    }

    /**
     * Create a new transaction object
     *
     * @param boolean $allowPending = false
     *
     * @return Transaction
     */
    abstract protected function doStartTransaction($isolationLevel = Transaction::REPEATABLE_READ);

    /**
     * {@inheritdoc}
     */
    final public function startTransaction($isolationLevel = Transaction::REPEATABLE_READ, $allowPending = false)
    {
        // Fetch transaction from the WeakRef if possible
        if ($this->currentTransaction && $this->currentTransaction->valid()) {
            $pending = $this->currentTransaction->get();

            // We need to proceed to additional checks to ensure the pending
            // transaction still exists and si started, using WeakRef the
            // object could already have been garbage collected
            if ($pending instanceof Transaction && $pending->isStarted()) {
                if (!$allowPending) {
                    throw new TransactionError("a transaction already been started, you cannot nest transactions");
                }

                return $pending;

            } else {
                unset($this->currentTransaction);
            }
        }

        // Acquire a weak reference if possible, this will allow the transaction
        // to fail upon __destruct() when the user leaves the transaction scope
        // without closing it properly. Without the ext-weakref extension, the
        // transaction will fail during PHP shutdown instead, errors will be
        // less understandable for the developper, and code will fail much later
        // and possibly run lots of things it should not. Since it's during a
        // pending transaction it will not cause data consistency bugs, it will
        // just make it harder to debug.
        $transaction = $this->doStartTransaction($isolationLevel);
        $this->currentTransaction = new \WeakRef($transaction);

        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    final public function isTransactionPending()
    {
        if ($this->currentTransaction) {
            if (!$this->currentTransaction->valid()) {
                unset($this->currentTransaction);
            } else {
                $pending = $this->currentTransaction->get();
                if (!$pending instanceof Transaction || !$pending->isStarted()) {
                    unset($this->currentTransaction);
                } else {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function select($relation, $alias = null)
    {
        $select = new SelectQuery($relation, $alias);
        $select->setConnection($this);

        return $select;
    }

    /**
     * {@inheritdoc}
     */
    public function update($relation, $alias = null)
    {
        $update = new UpdateQuery($relation, $alias);
        $update->setConnection($this);

        return $update;
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery($relation)
    {
        $insert = new InsertQueryQuery($relation);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    public function insertValues($relation)
    {
        $insert = new InsertValuesQuery($relation);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($relation, $alias = null)
    {
        $insert = new DeleteQuery($relation, $alias);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTables($relations)
    {
        if (!$relations) {
            throw new QueryError("cannot not truncate no tables");
        }

        // SQL-92 implementation - only one table at a time
        if (!is_array($relations)) {
            $relations = [$relations];
        }

        foreach ($relations as $relation) {
            $this->perform(sprintf("truncate %s", $this->escapeIdentifier($relation)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCastType($type)
    {
        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifierList($strings)
    {
        if (!$strings) {
            throw new GoatError("cannot not format an empty identifier list");
        }
        if (!is_array($strings)) {
            $strings = [$strings];
        }

        return implode(', ', array_map([$this, 'escapeIdentifier'], $strings));
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
     * @param mixed[] $overrides
     *   Parameters overrides
     *
     * @return array
     *   First value is the query string, second is the reworked array
     *   of parameters, if conversions were needed
     */
    private function rewriteQueryAndParameters($rawSQL, ArgumentBag $arguments, array $overrides = [])
    {
        $index      = 0;
        $parameters = $arguments->getAll($overrides);
        $done       = [];

        $rawSQL = preg_replace_callback('/\$(\*|\d+)(?:::([\w\."]+(?:\[\])?)|)?/', function ($matches) use (&$parameters, &$index, &$done, $arguments) {

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
                $type = $arguments->getTypeAt($index);
                if ($type) {
                    $parameters[$index] = $this->converter->extract($type, $value);
                } else {
                    $parameters[$index] = $this->converter->guess($value);
                }
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
        $overrides = [];

        if (!is_string($input)) {
            if (!$input instanceof Query) {
                throw new QueryError(sprintf("query must be a bare string or an instance of %s", Query::class));
            }

            $arguments = $input->getArguments();
            $input = $this->getSqlFormatter()->format($input);
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

        return $this->rewriteQueryAndParameters($input, $arguments, $overrides);
    }
}
