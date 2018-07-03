<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Error\TransactionError;
use Goat\Hydrator\HydratorMap;

/**
 * Base implementation that handles transaction for you, and prepare emulation
 * if your implementation cannot really prepare queries.
 */
trait RunnerTrait
{
    private $currentTransaction;
    private $hydratorMap;

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
    final public function setHydratorMap(HydratorMap $hydratorMap)
    {
        $this->hydratorMap = $hydratorMap;
    }

    /**
     * {@inheritdoc}
     */
    final public function getHydratorMap() : HydratorMap
    {
        return $this->hydratorMap;
    }
}
