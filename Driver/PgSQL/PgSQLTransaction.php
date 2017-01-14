<?php

namespace Goat\Driver\PgSQL;

use Goat\Core\Error\DriverError;
use Goat\Core\Error\TransactionError;
use Goat\Core\Error\TransactionFailedError;
use Goat\Core\Transaction\Transaction;

class PgSQLTransaction extends Transaction
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
     * Escape name list
     *
     * @param string[] $names
     *
     * @return string
     */
    private function escapeNameList(array $names)
    {
        $connection = $this->connection;

        return implode(
            ', ',
            array_map(
                function ($name) use ($connection) {
                    return $connection->escapeIdentifier($name);
                },
                $names
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransactionStart(int $isolationLevel)
    {
        try {
            // Set immediate constraint fail per default to be ISO with
            // backends that don't support deferable constraints
            $this->connection->perform(
                sprintf(
                    "START TRANSACTION ISOLATION LEVEL %s READ WRITE",
                    $this->getIsolationLevelString($isolationLevel)
                )
            );

        } catch (DriverError $e) {
            throw new TransactionError("transaction start failed", null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doChangeLevel(int $isolationLevel)
    {
        try {
            // Set immediate constraint fail per default to be ISO with
            // backends that don't support deferable constraints
            $this->connection->perform(
                sprintf(
                    "SET TRANSACTION ISOLATION LEVEL %s",
                    $this->getIsolationLevelString($isolationLevel)
                )
            );

        } catch (DriverError $e) {
            throw new TransactionError("transaction set failed", null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateSavepoint(string $name)
    {
        try {
            $this->connection->perform(
                sprintf(
                    "SAVEPOINT %s",
                    $this->connection->escapeIdentifier($name)
                )
            );
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
            $this->connection->perform(
                sprintf(
                    "ROLLBACK TO SAVEPOINT %s",
                    $this->connection->escapeIdentifier($name)
                )
            );
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
        try {
            $this
                ->connection
                ->perform(
                    sprintf(
                        "SET CONSTRAINTS %s DEFERRED",
                        $this->escapeNameList($constraints)
                    )
                )
            ;

        } catch (DriverError $e) {
            throw new TransactionFailedError(null, null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferAll()
    {
        try {
            $this->connection->perform("SET CONSTRAINTS ALL DEFERRED");
        } catch (DriverError $e) {
            throw new TransactionFailedError(null, null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateConstraints(array $constraints)
    {
        try {
            $this
                ->connection
                ->perform(
                    sprintf(
                        "SET CONSTRAINTS %s IMMEDIATE",
                        $this->escapeNameList($constraints)
                    )
                )
            ;

        } catch (DriverError $e) {
            throw new TransactionFailedError(null, null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateAll()
    {
        try {
            $this->connection->perform("SET CONSTRAINTS ALL IMMEDIATE");
        } catch (DriverError $e) {
            throw new TransactionFailedError(null, null, $e);
        }
    }
}
