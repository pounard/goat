<?php

declare(strict_types=1);

namespace Goat\Driver\MySQL;

use Goat\Error\DriverError;
use Goat\Error\TransactionError;
use Goat\Error\TransactionFailedError;
use Goat\Runner\AbstractTransaction;

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
            $this->runner->perform(
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
                if ($this->runner->isDebugEnabled()) {
                    trigger_error("transaction is nested into another, MySQL can't change the isolation level", E_USER_NOTICE);
                }
            } else {
                // MySQL >= 8 has a different syntax for transaction level which
                // is not based upon the standard SQL transaction levels.
                throw new TransactionError("transaction start failed", null, $e);
            }
        }

        try {
            $this->runner->perform("BEGIN");
        } catch (DriverError $e) {
            throw new TransactionError("transaction start failed", null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doChangeLevel(int $isolationLevel)
    {
        if ($this->runner->isDebugEnabled()) {
            trigger_error("MySQL does not support transaction level change during transaction", E_USER_NOTICE);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateSavepoint(string $name)
    {
        try {
            $this->runner->perform(sprintf(
                "SAVEPOINT %s",
                $this->runner->getEscaper()->escapeIdentifier($name)
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
            $this->runner->perform(sprintf(
                "ROLLBACK TO SAVEPOINT %s",
                $this->runner->getEscaper()->escapeIdentifier($name)
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
            $this->runner->perform("ROLLBACK");
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
            $this->runner->perform("COMMIT");
        } catch (DriverError $e) {
            throw new TransactionFailedError(null, null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferConstraints(array $constraints)
    {
        if ($this->runner->isDebugEnabled()) {
            trigger_error("MySQL does not support deferred constraints", E_USER_NOTICE);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferAll()
    {
        if ($this->runner->isDebugEnabled()) {
            trigger_error("MySQL does not support deferred constraints", E_USER_NOTICE);
        }
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
