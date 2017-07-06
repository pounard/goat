<?php

declare(strict_types=1);

namespace Goat\Tests\Driver;

use Goat\Error\GoatError;
use Goat\Error\TransactionError;
use Goat\Error\TransactionFailedError;
use Goat\Runner\RunnerInterface;
use Goat\Runner\Transaction;
use Goat\Tests\DriverTestCase;

class TransactionTest extends DriverTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(RunnerInterface $driver)
    {
        $driver->query("
            create temporary table transaction_test (
                id serial primary key,
                foo integer not null,
                bar varchar(255)
            )
        ");


        if ($driver->supportsDeferingConstraints()) {
            $driver->query("
                alter table transaction_test
                    add constraint transaction_test_foo
                    unique (foo)
                    deferrable
            ");
            $driver->query("
                alter table transaction_test
                    add constraint transaction_test_bar
                    unique (bar)
                    deferrable
            ");
        } else {
            $driver->query("
                alter table transaction_test
                    add constraint transaction_test_foo
                    unique (foo)
            ");
            $driver->query("
                alter table transaction_test
                    add constraint transaction_test_bar
                    unique (bar)
            ");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(RunnerInterface $driver)
    {
        $driver
            ->insertValues('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([1, 'a'])
            ->values([2, 'b'])
            ->values([3, 'c'])
            ->execute()
        ;
    }

    /**
     * Normal working transaction
     *
     * @dataProvider driverDataSource
     */
    public function testTransaction($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $transaction = $driver->startTransaction();
        $transaction->start();

        $driver
            ->insertValues('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->execute()
        ;

        $transaction->commit();

        $result = $driver
            ->select('transaction_test')
            ->orderBy('foo')
            ->execute()
        ;

        $this->assertCount(4, $result);
        $this->assertSame('a', $result->fetch()['bar']);
        $this->assertSame('b', $result->fetch()['bar']);
        $this->assertSame('c', $result->fetch()['bar']);
        $this->assertSame('d', $result->fetch()['bar']);
    }

    /**
     * Fail with immediate constraints (not deferred)
     *
     * @dataProvider driverDataSource
     */
    public function testImmediateTransactionFail($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $transaction = $driver
            ->startTransaction()
            ->start()
            ->deferred() // Defer all
            ->immediate('transaction_test_bar')
        ;

        try {

            // This should pass, foo constraint it deferred;
            // if backend does not support defering, this will
            // fail anyway, but the rest of the test is still
            // valid
            $driver
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should fail, bar constraint it immediate
            $driver
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->execute()
            ;

            $this->fail();

        } catch (TransactionFailedError $e) {
            // This must not happen because of immediate constraints
            $this->fail();
        } catch (GoatError $e) {
            // This should happen instead, arbitrary SQL error
            $transaction->rollback();
        }
    }

    /**
     * Fail with deferred constraints
     *
     * @dataProvider driverDataSource
     */
    public function testDeferredTransactionFail($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        if (!$driver->supportsDeferingConstraints()) {
            $this->markTestSkipped("driver does not support defering constraints");
        }

        $transaction = $driver
            ->startTransaction()
            ->start()
            ->immediate() // Immediate all
            ->deferred('transaction_test_foo')
        ;

        try {

            // This should pass, foo constraint it deferred
            $driver
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should fail, bar constraint it immediate
            $driver
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->execute()
            ;

            $this->fail();

        } catch (TransactionFailedError $e) {
            // This must not happen because of immediate constraints
            $this->fail();
        } catch (GoatError $e) {
            // This should happen instead, arbitrary SQL error
            $transaction->rollback();
        }
    }

    /**
     * Fail with ALL constraints deferred
     *
     * @dataProvider driverDataSource
     */
    public function testDeferredAllTransactionFail($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        if (!$driver->supportsDeferingConstraints()) {
            $this->markTestSkipped("driver does not support defering constraints");
        }

        $transaction = $driver
            ->startTransaction()
            ->start()
            ->deferred()
        ;

        try {

            // This should pass, all are deferred
            $driver
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should pass, all are deferred
            $driver
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->execute()
            ;

            $transaction->commit();

        } catch (TransactionFailedError $e) {
            // This is what should happen, error at commit time
            $transaction->rollback();
        } catch (GoatError $e) {
            // All constraints are deffered, we should not experience arbitrary
            // SQL errors at insert time
            $this->fail();
        }
    }

    /**
     * Tests that rollback works
     *
     * @dataProvider driverDataSource
     */
    public function testTransactionRollback($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $transaction = $driver->startTransaction();
        $transaction->start();

        $driver
            ->insertValues('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->execute()
        ;

        $transaction->rollback();

        $result = $driver
            ->select('transaction_test')
            ->execute()
        ;

        $this->assertCount(3, $result);
    }

    /**
     * Test that fetching a pending transaction is disallowed
     *
     * @dataProvider driverDataSource
     */
    public function testPendingAllowed($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $transaction = $driver->startTransaction();
        $transaction->start();

        // Fetch another transaction, it should fail
        try {
            $driver->startTransaction();
            $this->fail();
        } catch (TransactionError $e) {
        }

        // Fetch another transaction, it should NOT fail
        $t3 = $driver->startTransaction(Transaction::REPEATABLE_READ, true);
        // @todo temporary deactivating this test since that the profiling
        //   transaction makes it harder
        //$this->assertSame($t3, $transaction);
        $this->assertTrue($t3->isStarted());

        // Force rollback of the second, ensure previous is stopped too
        $t3->rollback();
        $this->assertFalse($transaction->isStarted());
    }

    /**
     * Internal test for testWeakRefAllowFailOnScopeClose()
     *
     * @param RunnerInterface $driver
     */
    protected function privateScopeForWeakRef(RunnerInterface $driver)
    {
        $transaction = $driver->startTransaction();
        $transaction->start();

        // Force fail
        unset($transaction);
    }

    /**
     * Test that when a transaction goes out of scope, it dies and raise an
     * exception if it was not closed: this can only work with the weakref
     * extension enabled
     *
     * @dataProvider driverDataSource
     */
    public function testWeakRefAllowFailOnScopeClose($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        if (!extension_loaded('weakref')) {
            $this->markTestSkipped("this test can only work with the WeakRef extension");
        }

        try {
            $this->privateScopeForWeakRef($driver);
            $this->fail();
        } catch (TransactionError $e) {
            // Success
        }
    }

    /**
     * Test the savepoint feature
     *
     * @dataProvider driverDataSource
     */
    public function testTransactionSavepoint($driverName, $class)
    {
        $driver = $this->createRunner($driverName, $class);

        $transaction = $driver->startTransaction();
        $transaction->start();

        $driver
            ->update('transaction_test')
            ->set('bar', 'z')
            ->condition('foo', 1)
            ->execute()
        ;

        $transaction->savepoint('bouyaya');

        $driver
            ->update('transaction_test')
            ->set('bar', 'y')
            ->condition('foo', 2)
            ->execute()
        ;

        $transaction->rollbackToSavepoint('bouyaya');
        $transaction->commit();

        $oneBar = $driver
            ->select('transaction_test')
            ->column('bar')
            ->condition('foo', 1)
            ->execute()
            ->fetchField()
        ;
        // This should have pass since it's before the savepoint
        $this->assertSame('z', $oneBar);

        $twoBar = $driver
            ->select('transaction_test')
            ->column('bar')
            ->condition('foo', 2)
            ->execute()
            ->fetchField()
        ;
        // This should not have pass thanks to savepoint
        $this->assertSame('b', $twoBar);
    }
}
