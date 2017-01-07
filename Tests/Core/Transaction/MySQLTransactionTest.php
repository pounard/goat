<?php

namespace Goat\Tests\Core\Transaction;

class MySQLTransactionTest extends AbstractTransactionTest
{
    protected function getDriver()
    {
        return 'PDO_MYSQL';
    }
}
