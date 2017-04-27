<?php

declare(strict_types=1);

namespace Goat\Driver\MySQL;

use Goat\Core\Error\DriverError;
use Goat\Core\Error\TransactionError;
use Goat\Core\Error\TransactionFailedError;
use Goat\Core\Transaction\AbstractTransaction;
use Goat\Core\Transaction\Transaction;

class MySQLTransaction extends AbstractTransaction
{
    /**
     * {@inheritdoc}
     */
    protected function doTransactionStart(int $isolationLevel)
    {
        try {
            // Transaction level cannot be changed while in the transaction,
            // so it must set before starting the transaction
            $this->driver->perform(
                sprintf(
                    "SET TRANSACTION ISOLATION LEVEL %s",
                    self::getIsolationLevelString($isolationLevel)
                )
            );

        } catch (DriverError $e) {
            // Gracefully continue without changing the transaction isolation
            // level, MySQL don't support it, but we cannot penalize our users;
            // beware that users might use a transaction with a lower level
            // than they asked for, and data consistency is not ensured anymore
            // that's the downside of using MySQL.
            if (1568 == $e->getCode()) {
                $this->driver->debugMessage("transaction is nested into another, MySQL can't change the isolation level", E_USER_NOTICE);
            } else {
                throw new TransactionError("transaction start failed", null, $e);
            }
        }

        try {
            $this->driver->perform("BEGIN");
        } catch (DriverError $e) {
            throw new TransactionError("transaction start failed", null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doChangeLevel(int $isolationLevel)
    {
        $this->driver->debugMessage("MySQL does not support transaction level change during transaction", E_USER_NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateSavepoint(string $name)
    {
        try {
            $this->driver->perform(sprintf(
                "SAVEPOINT %s",
                $this->driver->getEscaper()->escapeIdentifier($name)
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
            $this->driver->perform(sprintf(
                "ROLLBACK TO SAVEPOINT %s",
                $this->driver->getEscaper()->escapeIdentifier($name)
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
            $this->driver->perform("ROLLBACK");
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
            $this->driver->perform("COMMIT");
        } catch (DriverError $e) {
            throw new TransactionFailedError(null, null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferConstraints(array $constraints)
    {
        $this->driver->debugMessage("MySQL does not support deferred constraints", E_USER_NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferAll()
    {
        $this->driver->debugMessage("MySQL does not support deferred constraints", E_USER_NOTICE);
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
