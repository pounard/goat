<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Core\Error\DriverError;
use Goat\Core\Error\TransactionError;
use Goat\Core\Error\TransactionFailedError;
use Goat\Core\Transaction\AbstractTransaction;
use Goat\Core\Transaction\Transaction;

class PgSQLTransaction extends AbstractTransaction
{
    /**
     * Escape name list
     *
     * @param string[] $names
     *
     * @return string
     */
    private function escapeNameList(array $names)
    {
        $driver = $this->driver;

        return implode(
            ', ',
            array_map(
                function ($name) use ($driver) {
                    return $driver->getEscaper()->escapeIdentifier($name);
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
            $this->driver->perform(
                sprintf(
                    "START TRANSACTION ISOLATION LEVEL %s READ WRITE",
                    self::getIsolationLevelString($isolationLevel)
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
            $this->driver->perform(
                sprintf(
                    "SET TRANSACTION ISOLATION LEVEL %s",
                    self::getIsolationLevelString($isolationLevel)
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
            $this->driver->perform(
                sprintf(
                    "SAVEPOINT %s",
                    $this->driver->getEscaper()->escapeIdentifier($name)
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
            $this->driver->perform(
                sprintf(
                    "ROLLBACK TO SAVEPOINT %s",
                    $this->driver->getEscaper()->escapeIdentifier($name)
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
        try {
            $this
                ->driver
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
            $this->driver->perform("SET CONSTRAINTS ALL DEFERRED");
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
                ->driver
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
            $this->driver->perform("SET CONSTRAINTS ALL IMMEDIATE");
        } catch (DriverError $e) {
            throw new TransactionFailedError(null, null, $e);
        }
    }
}
