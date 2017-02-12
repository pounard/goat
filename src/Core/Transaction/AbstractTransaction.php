<?php

declare(strict_types=1);

namespace Goat\Core\Transaction;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\DebuggableTrait;
use Goat\Core\Error\TransactionError;

/**
 * Base implementation of the Transaction interface that prevents logic errors.
 */
abstract class AbstractTransaction implements Transaction
{
    use DebuggableTrait;

    /**
     * Default savepoint name prefix
     */
    const SAVEPOINT_PREFIX = 'gsp_';

    /**
     * Get transaction level string
     *
     * @param int $isolationLevel
     *
     * @return string
     */
    public static function getIsolationLevelString(int $isolationLevel)
    {
        switch ($isolationLevel) {

            case Transaction::READ_UNCOMMITED:
                return 'READ UNCOMMITTED';

            case Transaction::READ_COMMITED:
                return 'READ COMMITTED';

            case Transaction::REPEATABLE_READ:
                return 'REPEATABLE READ';

            case Transaction::SERIALIZABLE:
                return 'SERIALIZABLE';

            default:
                throw new TransactionError(sprintf("%s: unknown transaction level", $isolationLevel));
        }
    }

    /**
     * @var ConnectionInterface
     */
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
    final public function __construct(int $isolationLevel = self::REPEATABLE_READ)
    {
        $this->level($isolationLevel);
    }

    /**
     * Set connection
     *
     * @param ConnectionInterface $connection
     *
     * @return Transaction
     */
    final public function setConnection(ConnectionInterface $connection) : Transaction
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
    abstract protected function doTransactionStart(int $isolationLevel);

    /**
     * Change transaction level
     *
     * @param int $isolationLevel
     *   One of the Transaction::* constants
     */
    abstract protected function doChangeLevel(int $isolationLevel);

    /**
     * Create savepoint
     */
    abstract protected function doCreateSavepoint(string $name);

    /**
     * Rollback to savepoint
     */
    abstract protected function doRollbackToSavepoint(string $name);

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
     * @return Transaction
     */
    public function level(int $isolationLevel) : Transaction
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
     * Is transaction started
     *
     * @return bool
     */
    public function isStarted() : bool
    {
        return $this->started;
    }

    /**
     * Start the transaction
     *
     * @return Transaction
     */
    public function start() : Transaction
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
     * @return Transaction
     */
    public function immediate($constraint = null) : Transaction
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
     * @return Transaction
     */
    public function deferred($constraint = null) : Transaction
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
    public function savepoint(string $name = null) : string
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

        $this->savepoints[$name] = true;

        return $name;
    }

    /**
     * Explicit transaction commit
     *
     * @return Transaction
     */
    public function commit() : Transaction
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
     *
     * @return Transaction
     */
    public function rollback() : Transaction
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
     * @return Transaction
     */
    public function rollbackToSavepoint(string $name) : Transaction
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
