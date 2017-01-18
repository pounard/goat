<?php

declare(strict_types=1);

namespace Goat\Core\Profiling;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Transaction\Transaction;

/**
 * This specific transaction implementation will keep track of everything
 * that's happening, timings, counts, etc...
 */
class ProfilingTransaction implements Transaction
{
    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var ProfilingConnectionProxy
     */
    private $profiler;

    /**
     * @var Timer
     */
    private $timer;

    /**
     * Default constructor
     *
     * @param ProfilingConnectionProxy $profiler
     * @param Transaction $transaction
     * @param Timer $timer
     */
    public function __construct(ProfilingConnectionProxy $profiler, Transaction $transaction, Timer $timer)
    {
        $this->transaction = $transaction;
        $this->profiler = $profiler;

        // Timer is supposed to be start when calling startTransaction() for
        // the first time, since per documentation, connections are supposed to
        // start it directly on this call
        $this->timer = $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(ConnectionInterface $connection)
    {
        // This is a noop.
    }

    /**
     * {@inheritdoc}
     */
    public function level(int $isolationLevel) : Transaction
    {
        $this->transaction->level($isolationLevel);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted() : bool
    {
        return $this->transaction->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function start() : Transaction
    {
        $this->transaction->start();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function immediate($constraint = null) : Transaction
    {
        $this->transaction->immediate($constraint);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deferred($constraint = null) : Transaction
    {
        $this->transaction->deferred($constraint);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function savepoint(string $name = null) : string
    {
        return $this->transaction->savepoint($name);
    }

    /**
     * {@inheritdoc}
     */
    public function commit() : Transaction
    {
        $this->transaction->commit();

        // Do not count commit in case of failure
        $this->profiler->addTo('transation_commit_count');
        $this->profiler->addTo('transation_time', $this->timer->stop());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback() : Transaction
    {
        try {
            return $this->transaction->rollback();
        } catch (\Exception $e) {
            $this->profiler->addTo('exception');
            throw $e;
        } finally {
            // Always count rollbacks, no matter if they fail
            $this->profiler->addTo('transation_rollback_count');
            $this->profiler->addTo('transation_time', $this->timer->stop());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackToSavepoint(string $name) : Transaction
    {
        $this->transaction->rollbackToSavepoint($name);

        return $this;
    }
}
