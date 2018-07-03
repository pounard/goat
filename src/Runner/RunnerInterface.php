<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Hydrator\HydratorMap;
use Goat\Query\QueryFactoryInterface;
use Goat\Query\QueryRunnerInterface;
use Goat\Query\Writer\EscaperInterface;

/**
 * Stripped down representation of a connection/driver that can run queries.
 */
interface RunnerInterface extends QueryFactoryInterface, QueryRunnerInterface
{
    /**
     * Toggle debug mode
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug = true);

    /**
     * Is debug mode enabled
     *
     * @return bool
     */
    public function isDebugEnabled() : bool;

    /**
     * Get escaper
     *
     * @return EscaperInterface
     */
    public function getEscaper() : EscaperInterface;

    /**
     * Get driver name
     *
     * @return string
     */
    public function getDriverName() : string;

    /**
     * Does the backend supports RETURNING clauses
     *
     * @return bool
     */
    public function supportsReturning() : bool;

    /**
     * Does the backend supports defering constraints
     *
     * @return bool
     */
    public function supportsDeferingConstraints() : bool;

    /**
     * Creates a new transaction
     *
     * If a transaction is pending, continue the same transaction by adding a
     * new savepoint that will be transparently rollbacked in case of failure
     * in between.
     *
     * @param int $isolationLevel
     *   Default transaction isolation level, it is advised that you set it
     *   directly at this point, since some drivers don't allow isolation
     *   level changes while transaction is started
     * @param bool $allowPending = false
     *   If set to true, explicitely allow to fetch the currently pending
     *   transaction, else errors will be raised
     *
     * @throws \Goat\Error\TransactionError
     *   If you asked a new transaction while another one is opened, or if the
     *   transaction fails starting
     *
     * @return Transaction
     */
    public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false) : Transaction;

    /**
     * Is there a pending transaction
     *
     * @return bool
     */
    public function isTransactionPending() : bool;

    /**
     * Prepare query
     *
     * @param string|\Goat\Query\Query $query
     *   Bare SQL or Query instance
     * @param string $identifier
     *   Query unique identifier, if null given one will be generated
     *
     * @return string
     *   The given or generated identifier
     */
    public function prepareQuery($query, string $identifier = null) : string;

    /**
     * Prepare query
     *
     * @param string $identifier
     *   Query unique identifier
     * @param mixed[] $parameters
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return ResultIteratorInterface
     */
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface;

    /**
     * Set converter map
     */
    public function setConverter(ConverterInterface $converter);

    /**
     * Set hydrator map
     */
    public function setHydratorMap(HydratorMap $hydratorMap);

    /**
     * Get hydrator map
     */
    public function getHydratorMap() : HydratorMap;
}
