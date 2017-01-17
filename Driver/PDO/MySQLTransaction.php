<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Core\Error\DriverError;
use Goat\Core\Error\TransactionError;
use Goat\Core\Error\TransactionFailedError;
use Goat\Core\Transaction\Transaction;

class MySQLTransaction extends Transaction
{
    /**
     * Get transaction level string
     *
     * @param int $isolationLevel
     *
     * @return string
     */
    private function getIsolationLevelString(int $isolationLevel)
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
     * {@inheritdoc}
     */
    protected function doTransactionStart(int $isolationLevel)
    {
        try {
            // Transaction level cannot be changed while in the transaction,
            // so it must set before starting the transaction
            $this->connection->perform(
                sprintf(
                    "SET TRANSACTION ISOLATION LEVEL %s",
                    $this->getIsolationLevelString($isolationLevel)
                )
            );

        } catch (DriverError $e) {
            // Gracefully continue without changing the transaction isolation
            // level, MySQL don't support it, but we cannot penalize our users;
            // beware that users might use a transaction with a lower level
            // than they asked for, and data consistency is not ensured anymore
            // that's the downside of using MySQL.
            if (1568 == $e->getCode()) {
                $this->connection->debugMessage("transaction is nested into another, MySQL can't change the isolation level", E_USER_NOTICE);
            } else {
                throw new TransactionError("transaction start failed", null, $e);
            }
        }

        try {
            $this->connection->perform("BEGIN");
        } catch (DriverError $e) {
            throw new TransactionError("transaction start failed", null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doChangeLevel(int $isolationLevel)
    {
        $this->connection->debugMessage("MySQL does not support transaction level change during transaction", E_USER_NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateSavepoint(string $name)
    {
        try {
            $this->connection->perform(sprintf(
                "SAVEPOINT %s",
                $this->connection->escapeIdentifier($name)
            ));
        } catch (DriverError $e) {
            throw new TransactionError(sprintf("%s: create savepoint failed", $name), null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doRollbackToSavepoint(string $name)
    {
        try {
            $this->connection->perform(sprintf(
                "ROLLBACK TO SAVEPOINT %s",
                $this->connection->escapeIdentifier($name)
            ));
        } catch (DriverError $e) {
            throw new TransactionError(sprintf("%s: rollback to savepoint failed", $name), null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doRollback()
    {
        try {
            $this->connection->perform("ROLLBACK");
        } catch (DriverError $e) {
            throw new TransactionError(null, null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doCommit()
    {
        try {
            $this->connection->perform("COMMIT");
        } catch (DriverError $e) {
            throw new TransactionFailedError(null, null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferConstraints(array $constraints)
    {
        $this->connection->debugMessage("MySQL does not support deferred constraints", E_USER_NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferAll()
    {
        $this->connection->debugMessage("MySQL does not support deferred constraints", E_USER_NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateConstraints(array $constraints)
    {
        // Do nothing, as MySQL always check constraints immediatly
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateAll()
    {
        // Do nothing, as MySQL always check constraints immediatly
    }
}
