<?php

declare(strict_types=1);

namespace Goat\Core\Profiling;

use Goat\Core\Transaction\Transaction;
use Goat\Driver\AbstractDriverProxy;
use Goat\Driver\DriverInterface;
use Goat\Query\DeleteQuery;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\SelectQuery;
use Goat\Query\Statement;
use Goat\Query\UpdateQuery;
use Goat\Runner\EmptyResultIterator;
use Goat\Runner\ResultIteratorInterface;

/**
 * Connection proxy that emits events via Symfony's EventDispatcher
 *
 * @codeCoverageIgnore
 */
class ProfilingConnectionProxy extends AbstractDriverProxy
{
    private $connection;
    private $data = [];

    /**
     * Default constructor
     *
     * @param DriverInterface $connection
     */
    public function __construct(DriverInterface $connection)
    {
        $this->connection = $connection;
        $this->data = [
            'exception' => 0,
            'execute_count' => 0,
            'execute_time' => 0,
            'perform_count' => 0,
            'perform_time' => 0,
            'prepare_count' => 0,
            'query_count' => 0,
            'query_time' => 0,
            'total_count' => 0,
            'total_time' => 0,
            'transaction_commit_count' => 0,
            'transaction_count' => 0,
            'transaction_rollback_count' => 0,
            'transaction_time' => 0,
            'queries' => [],
        ];
    }

    /**
     * Get collected data
     *
     * @return array
     */
    public function getCollectedData() : array
    {
        return $this->data;
    }

    /**
     * Append value to a counter or timer
     *
     * @param string $name
     * @param mixed $value
     */
    public function addTo(string $name, $value = 1)
    {
        if (!isset($this->data[$name])) {
            $this->data[$name] = $value;
        } else {
            $this->data[$name] += $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getInnerConnection() : DriverInterface
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        $timer = new Timer();
        $this->data['query_count']++;
        $this->data['total_count']++;
        $ret = null;

        try {
            $connection = $this->getInnerConnection();

            if ($query instanceof Statement) {
                $rawSQL = $connection->getFormatter()->format($query);
            } else {
                $rawSQL = (string)$query;
            }
            $this->data['queries'][] = ['sql' => $rawSQL, 'params' => $parameters];

            $ret = $connection->query($query, $parameters ?? [], $options);

        } catch (\Exception $e) {
            $this->data['exception']++;
            throw $e;
        } finally {
            $duration = $timer->stop();
            // Ignore empty result iterator, this means it fallbacked on perform()
            if ($ret instanceof EmptyResultIterator) {
                $this->data['query_count']--;
                $this->data['total_count']--;
            } else {
                $this->data['query_time'] += $duration;
                $this->data['total_time'] += $duration;
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, array $parameters = null, $options = null) : int
    {
        $timer = new Timer();
        $this->data['perform_count']++;
        $this->data['total_count']++;

        try {
            $connection = $this->getInnerConnection();

            if ($query instanceof Statement) {
                $rawSQL = $connection->getFormatter()->format($query);
            } else {
                $rawSQL = (string)$query;
            }
            $this->data['queries'][] = ['sql' => $rawSQL, 'params' => $parameters];

            $ret = $connection->perform($query, $parameters ?? [], $options);

        } catch (\Exception $e) {
            $this->data['exception']++;
            throw $e;
        } finally {
            $duration = $timer->stop();
            $this->data['perform_time'] += $duration;
            $this->data['total_time'] += $duration;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null) : string
    {
        $this->data['prepare_count']++;

        $ret = $this->getInnerConnection()->prepareQuery($query, $identifier);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        $timer = new Timer();
        $this->data['execute_count']++;
        $this->data['total_count']++;

        try {
            $ret = $this->getInnerConnection()->executePreparedQuery($identifier, $parameters ?? [], $options);
        } catch (\Exception $e) {
            $this->data['exception']++;
            throw $e;
        } finally {
            $duration = $timer->stop();
            $this->data['execute_time'] += $duration;
            $this->data['total_time'] += $duration;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false) : Transaction
    {
        $this->data['transaction_count']++;

        $transaction = $this->getInnerConnection()->startTransaction($isolationLevel, $allowPending);
        $ret = new ProfilingTransaction($this, $transaction, new Timer());

        return $ret;
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
}
