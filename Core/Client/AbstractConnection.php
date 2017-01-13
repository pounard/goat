<?php

namespace Goat\Core\Client;

use Goat\Core\Converter\ConverterAwareTrait;
use Goat\Core\Converter\ConverterMap;
use Goat\Core\DebuggableTrait;
use Goat\Core\Error\GoatError;
use Goat\Core\Error\QueryError;
use Goat\Core\Error\TransactionError;
use Goat\Core\Hydrator\HydratorMap;
use Goat\Core\Query\DeleteQuery;
use Goat\Core\Query\InsertQueryQuery;
use Goat\Core\Query\InsertValuesQuery;
use Goat\Core\Query\Query;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\SqlFormatter;
use Goat\Core\Query\SqlFormatterInterface;
use Goat\Core\Query\UpdateQuery;
use Goat\Core\Transaction\Transaction;

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
    protected $configuration = [];
    protected $dsn;
    protected $formatter;
    protected $hydratorMap;

    /**
     * Constructor
     *
     * @param Dsn $dsn
     * @param string[] $configuration
     */
    public function __construct(Dsn $dsn, array $configuration = [])
    {
        $this->dsn = $dsn;
        $this->configuration = $configuration;
        $this->formatter = $this->createFormatter();

        // Register an empty instance for the converter, in case.
        $this->converter = new ConverterMap();
    }

    /**
     * Create SQL formatter
     *
     * @return SqlFormatterInterface
     */
    protected function createFormatter() : SqlFormatterInterface
    {
        return new SqlFormatter($this);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints() : bool
    {
        return true;
    }

    /**
     * Create a new transaction object
     *
     * @param bool $allowPending = false
     *
     * @return Transaction
     */
    abstract protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ) : Transaction;

    /**
     * {@inheritdoc}
     */
    final public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false) : Transaction
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
     * Do create iterator
     *
     * @param ...$constructorArgs
     *   Driver specific parameters
     */
    abstract protected function doCreateResultIterator(...$constructorArgs) : ResultIteratorInterface;

    /**
     * Create the result iterator instance
     *
     * @param $options = null
     *   Query options
     * @param ...$constructorArgs
     *   Driver specific parameters
     *
     * @return ResultIteratorInterface
     */
    final protected function createResultIterator($options = null, ...$constructorArgs) : ResultIteratorInterface
    {
        $result = $this->doCreateResultIterator(...$constructorArgs);
        $result->setConverter($this->converter);

        if ($options) {
            if (is_string($options)) {
                $options = ['class' => $options];
            } else if (!is_array($options)) {
                throw new QueryError("options must be a valid class name or an array of options");
            }
        }

        if (isset($options['class'])) {
            // Class can be either an alias or a valid class name, the hydrator
            // will proceed with all runtime checks to ensure that.
            $result->setHydrator($this->hydratorMap->get($options['class']));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    final public function isTransactionPending() : bool
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
    final public function select(string $relationName, string $alias = null) : SelectQuery
    {
        $select = new SelectQuery($relationName, $alias);
        $select->setConnection($this);

        return $select;
    }

    /**
     * {@inheritdoc}
     */
    final public function update(string $relationName, string $alias = null) : UpdateQuery
    {
        $update = new UpdateQuery($relationName, $alias);
        $update->setConnection($this);

        return $update;
    }

    /**
     * {@inheritdoc}
     */
    final public function insertQuery(string $relationName) : InsertQueryQuery
    {
        $insert = new InsertQueryQuery($relationName);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    final public function insertValues(string $relationName) : InsertValuesQuery
    {
        $insert = new InsertValuesQuery($relationName);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    final public function delete(string $relationName, string $alias = null) : DeleteQuery
    {
        $insert = new DeleteQuery($relationName, $alias);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTables($relationNames)
    {
        if (!$relationNames) {
            throw new QueryError("cannot not truncate no tables");
        }

        // SQL-92 implementation - only one table at a time
        if (!is_array($relationNames)) {
            $relationNames = [$relationNames];
        }

        foreach ($relationNames as $relation) {
            $this->perform(sprintf("truncate %s", $this->escapeIdentifier($relation)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCastType(string $type) : string
    {
        return $type;
    }

    /**
     * {@inheritdoc}
     */
    final public function escapeIdentifierList($strings) : string
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
    * {@inheritdoc}
     */
    final public function setHydratorMap(HydratorMap $hydratorMap)
    {
        $this->hydratorMap = $hydratorMap;
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
        // This is supposedly SQL-92 standard compliant
        return sprintf("cast(%s as %s)", $placeholder, $type);
    }

    /**
     * Get the default anonymous placeholder for queries
     *
     * @param int $index
     *   The numerical index position of the placeholder value
     */
    protected function getPlaceholder(int $index) : string
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
    private function rewriteQueryAndParameters(string $rawSQL, ArgumentBag $arguments, array $overrides = []) : array
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
    final protected function getProperSql($input, $parameters = null) : array
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
