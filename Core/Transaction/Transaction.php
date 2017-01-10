<?php

namespace Goat\Core\Transaction;

use Goat\Core\Client\ConnectionAwareInterface;
use Goat\Core\Client\ConnectionInterface;
use Goat\Core\DebuggableTrait;
use Goat\Core\Error\TransactionError;

/**
 * Represents a transaction
 *
 * Each driver must implement it.
 */
abstract class Transaction implements ConnectionAwareInterface
{
    const SAVEPOINT_PREFIX = 'gsp_';

    const READ_UNCOMMITED = 1;
    const READ_COMMITED = 2;
    const REPEATABLE_READ = 3;
    const SERIALIZABLE = 4;

    use DebuggableTrait;

    protected $connection;
    private $isolationLevel = self::REPEATABLE_READ;
    private $savepoint = 0;
    private $savepoints = [];
    private $started = false;

    /**
     * Default constructor
     *
     * @param int $isolationLevel
     *   One of the Transaction::* constants
     */
    final public function __construct($isolationLevel = self::REPEATABLE_READ)
    {
        $this->level($isolationLevel);
    }

    /**
     * Set connection
     *
     * @param ConnectionInterface $connection
     *
     * @return $this
     */
    final public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->setDebug($connection->isDebugEnabled());

        return $this;
    }

    /**
     * Default destructor
     *
     * Started transactions should not be left opened, this will force a
     * transaction rollback and throw an exception
     */
    public function __destruct()
    {
        if ($this->started) {
            $this->rollback();

            throw new TransactionError(sprintf("transactions should never be left opened"));
        }
    }

    /**
     * Starts the transaction
     *
     * @param int $isolationLevel
     *   One of the Transaction::* constants
     */
    abstract protected function doTransactionStart($isolationLevel);

    /**
     * Change transaction level
     *
     * @param int $isolationLevel
     *   One of the Transaction::* constants
     */
    abstract protected function doChangeLevel($isolationLevel);

    /**
     * Create savepoint
     */
    abstract protected function doCreateSavepoint($name);

    /**
     * Rollback to savepoint
     */
    abstract protected function doRollbackToSavepoint($name);

    /**
     * Rollback
     */
    abstract protected function doRollback();

    /**
     * Commit
     */
    abstract protected function doCommit();

    /**
     * Defer given constraints
     *
     * @param string[] $constraints
     *   Constraint name list
     */
    abstract protected function doDeferConstraints(array $constraints);

    /**
     * Defer all constraints
     */
    abstract protected function doDeferAll();

    /**
     * Set given constraints as immediate
     *
     * @param string[] $constraints
     *   Constraint name list
     */
    abstract protected function doImmediateConstraints(array $constraints);

    /**
     * Set all constraints as immediate
     */
    abstract protected function doImmediateAll();

    /**
     * Change transaction level
     *
     * @param int $isolationLevel
     *   One of the Transaction::* constants
     *
     * @return $this
     */
    public function level($isolationLevel)
    {
        if ($isolationLevel === $this->isolationLevel) {
            return $this; // Nothing to be done
        }

        if ($this->started) {
            $this->doChangeLevel($isolationLevel);
        }

        return $this;
    }

    /**
     * Start the transaction
     *
     * @return $this
     */
    public function start()
    {
        $this->doTransactionStart($this->isolationLevel);

        $this->started = true;

        return $this;
    }

    /**
     * Set as immediate all or a set of constraints
     *
     * @param string|string[]
     *   If set to null, all constraints are set immediate
     *   If a string or a string array, only the given constraint
     *   names are set as immediate
     *
     * @return $this
     */
    public function immediate($constraint = null)
    {
        if ($constraint) {
            if (!is_array($constraint)) {
                $constraint = [$constraint];
            }
            $this->doImmediateConstraints($constraint);
        } else {
            $this->doImmediateAll();
        }

        return $this;
    }

    /**
     * Defer all or a set of constraints
     *
     * @param string|string[]
     *   If set to null, all constraints are set immediate
     *   If a string or a string array, only the given constraint
     *   names are set as immediate
     *
     * @return $this
     */
    public function deferred($constraint = null)
    {
        if ($constraint) {
            if (!is_array($constraint)) {
                $constraint = [$constraint];
            }
            $this->doDeferConstraints($constraint);
        } else {
            $this->doDeferAll();
        }

        return $this;
    }

    /**
     * Creates a savepoint and return its name
     *
     * @param string $name
     *   Optional user given savepoint name, if none provided a name will be
     *   automatically computed using a serial
     *
     * @throws TransactionError
     *   If savepoint name already exists
     *
     * @return string
     *   The savepoint realname
     */
    public function savepoint($name = null)
    {
        if (!$this->started) {
            throw new TransactionError(sprintf("can not commit a non-running transaction"));
        }

        if (!$name) {
            $name = self::SAVEPOINT_PREFIX . (++$this->savepoint);
        }

        if (isset($this->savepoints[$name])) {
            throw new TransactionError(sprintf("%s: savepoint already exists", $name));
        }

        $this->doCreateSavepoint($name);

        return $name;
    }

    /**
     * Explicit transaction commit
     */
    public function commit()
    {
        if (!$this->started) {
            throw new TransactionError(sprintf("can not commit a non-running transaction"));
        }

        $this->doCommit();

        // This code will be reached only if the commit failed, the transaction
        // not beeing stopped at the application level allows you to call
        // rollbacks later.
        $this->started = false;

        return $this;
    }

    /**
     * Explicit transaction rollback
     */
    public function rollback()
    {
        if (!$this->started) {
            throw new TransactionError(sprintf("can not rollback a non-running transaction"));
        }

        // Even if the rollback fails and throw exceptions, this transaction
        // is dead in the woods, just mark it as stopped.
        $this->started = false;

        $this->doRollback();

        return $this;
    }

    /**
     * Rollback to savepoint
     *
     * @param string $name
     *   Savepoint name
     *
     * @return $this
     */
    public function rollbackToSavepoint($name)
    {
        if (!$this->started) {
            throw new TransactionError(sprintf("can not rollback to savepoint in a non-running transaction"));
        }
        if (!isset($this->savepoints[$name])) {
            throw new TransactionError(sprintf("%s: savepoint does not exists or is not handled by this object", $name));
        }

        $this->doRollbackToSavepoint($name);

        return $this;
    }
}
