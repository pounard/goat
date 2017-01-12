<?php

namespace Goat\Tests\Driver;

use Goat\Core\Client\ConnectionInterface;

class PgSQLTransactionTest extends AbstractTransactionTest
{
    protected function getDriver()
    {
        return 'PDO_PGSQL';
    }

    /**
     * MySQL does not supports DEFERRABLE so we add it here.
     */
    protected function createTestSchema(ConnectionInterface $connection)
    {
        $connection->query("
            create temporary table transaction_test (
                id serial primary key,
                foo integer not null,
                bar varchar(255)
            )
        ");
        $connection->query("
            alter table transaction_test
                add constraint transaction_test_foo
                unique (foo)
                deferrable
        ");
        $connection->query("
            alter table transaction_test
                add constraint transaction_test_bar
                unique (bar)
                deferrable
        ");
    }
}
