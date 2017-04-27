<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Converter\ConverterAwareTrait;
use Goat\Converter\ConverterMap;
use Goat\Core\DebuggableTrait;
use Goat\Core\Error\QueryError;
use Goat\Core\Error\TransactionError;
use Goat\Core\Hydrator\HydratorMap;
use Goat\Core\Session\Dsn;
use Goat\Core\Transaction\Transaction;
use Goat\Query\DeleteQuery;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\Query;
use Goat\Query\SelectQuery;
use Goat\Query\UpdateQuery;
use Goat\Query\Writer\EscaperAwareTrait;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\ResultIteratorInterface;

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
abstract class AbstractDriver implements DriverInterface
{
    use ConverterAwareTrait;
    use DebuggableTrait;
    use EscaperAwareTrait;

    private $currentTransaction;
    private $databaseInfo;
    protected $configuration = [];
    protected $converter;
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
        $this->escaper = $this->createEscaper();
        $this->formatter = $this->createFormatter();

        // Register an empty instance for the converter, in case.
        $this->setConverter(new ConverterMap());
    }

    /**
     * Destructor, enforces connection close on dispose
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterMap $converter)
    {
        $this->converter = $converter;
        $this->formatter->setConverter($converter);
    }

    /**
     * Create SQL formatter
     *
     * @return FormatterInterface
     */
    abstract protected function createFormatter() : FormatterInterface;

    /**
     * Create SQL escaper
     *
     * @return EscaperInterface
     */
    abstract protected function createEscaper() : EscaperInterface;

    /**
     * Fetch database information
     *
     * @return array
     *   Must contain the following key:
     *     -name: database server name
     *     - version: database server version
     *   It might contain abitrary other keys:
     *     - build
     *     - ...
     */
    abstract protected function fetchDatabaseInfo() : array;

    /**
     * Load database information
     */
    private function loadDatabaseInfo()
    {
        if (!$this->databaseInfo) {
            $this->databaseInfo = $this->fetchDatabaseInfo();
        }
    }

    /**
     * Get database server information
     *
     * @return string[]
     */
    final public function getDatabaseInfo() : array
    {
        $this->loadDatabaseInfo();

        return $this->databaseInfo;
    }

    /**
     * {@inheritdoc}
     */
    final public function getDatabaseName() : string
    {
        $this->loadDatabaseInfo();

        return $this->databaseInfo['name'];
    }

    /**
     * {@inheritdoc}
     */
    final public function getDatabaseVersion() : string
    {
        $this->loadDatabaseInfo();

        return $this->databaseInfo['version'];
    }

    /**
     * {@inheritdoc}
     */
    final public function getDriverName() : string
    {
        return $this->dsn->getDriver();
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
                $this->currentTransaction = null;
            } else {
                $pending = $this->currentTransaction->get();
                if (!$pending instanceof Transaction || !$pending->isStarted()) {
                    $this->currentTransaction = null;
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
    final public function select($relation, string $alias = null) : SelectQuery
    {
        $select = new SelectQuery($relation, $alias);
        $select->setConnection($this);

        return $select;
    }

    /**
     * {@inheritdoc}
     */
    final public function update($relation, string $alias = null) : UpdateQuery
    {
        $update = new UpdateQuery($relation, $alias);
        $update->setConnection($this);

        return $update;
    }

    /**
     * {@inheritdoc}
     */
    final public function insertQuery($relation) : InsertQueryQuery
    {
        $insert = new InsertQueryQuery($relation);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    final public function insertValues($relation) : InsertValuesQuery
    {
        $insert = new InsertValuesQuery($relation);
        $insert->setConnection($this);

        return $insert;
    }

    /**
     * {@inheritdoc}
     */
    final public function delete($relation, string $alias = null) : DeleteQuery
    {
        $insert = new DeleteQuery($relation, $alias);
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
            $this->perform(sprintf("truncate %s", $this->getEscaper()->escapeIdentifier($relation)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEscaper() : EscaperInterface
    {
        return $this->escaper;
    }

    /**
     * {@inheritdoc}
     */
    final public function setHydratorMap(HydratorMap $hydratorMap)
    {
        $this->hydratorMap = $hydratorMap;
    }
}
