<?php

declare(strict_types=1);

namespace Goat\Core\EventDispatcher;

/**
 * Event names constants
 */
final class GoatEvents
{
    /**
     * Perform is run only for perform()
     */
    const PERFORM = 'goat:perform';

    /**
     * A query is prepared
     */
    const PREPARE = 'goat:prepare';

    /**
     * A prepared query is executed
     */
    const PREPARE_EXECUTE = 'goat:prepare:execute';

    /**
     * Query is send for all of query(), perform() and execute()
     */
    const QUERY = 'goat:query';

    /**
     * A transaction is starting
     */
    const TRANSACTION_START = 'goat:transaction:start';
}
