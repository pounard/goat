<?php

namespace Goat\Tests\Driver;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Error\GoatError;
use Goat\Core\Error\TransactionError;
use Goat\Core\Error\TransactionFailedError;
use Goat\Core\Transaction\Transaction;
use Goat\Tests\ConnectionAwareTest;

abstract class AbstractTransactionTest extends ConnectionAwareTest
{
    /**
     * {@inheritdoc}
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
        ");
        $connection->query("
            alter table transaction_test
                add constraint transaction_test_bar
                unique (bar)
        ");
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(ConnectionInterface $connection)
    {
        $connection
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
     */
    public function testTransaction()
    {
        $connection = $this->getConnection();

        $transaction = $connection->startTransaction();
        $transaction->start();

        $connection
            ->insertValues('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->execute()
        ;

        $transaction->commit();

        $result = $connection
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
     */
    public function testImmediateTransactionFail()
    {
        $connection = $this->getConnection();

        $transaction = $connection
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
            $connection
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should fail, bar constraint it immediate
            $connection
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
     */
    public function testDeferredTransactionFail()
    {
        $connection = $this->getConnection();

        if (!$connection->supportsDeferingConstraints()) {
            $this->markTestSkipped("driver does not support defering constraints");
        }

        $transaction = $connection
            ->startTransaction()
            ->start()
            ->immediate() // Immediate all
            ->deferred('transaction_test_foo')
        ;

        try {

            // This should pass, foo constraint it deferred
            $connection
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should fail, bar constraint it immediate
            $connection
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
     */
    public function testDeferredAllTransactionFail()
    {
        $connection = $this->getConnection();

        if (!$connection->supportsDeferingConstraints()) {
            $this->markTestSkipped("driver does not support defering constraints");
        }

        $transaction = $connection
            ->startTransaction()
            ->start()
            ->deferred()
        ;

        try {

            // This should pass, all are deferred
            $connection
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should pass, all are deferred
            $connection
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
     */
    public function testTransactionRollback()
    {
        $connection = $this->getConnection();

        $transaction = $connection->startTransaction();
        $transaction->start();

        $connection
            ->insertValues('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->execute()
        ;

        $transaction->rollback();

        $result = $connection
            ->select('transaction_test')
            ->execute()
        ;

        $this->assertCount(3, $result);
    }

    /**
     * Test that fetching a pending transaction is disallowed
     */
    public function testPendingAllowed()
    {
        $connection = $this->getConnection();

        $transaction = $connection->startTransaction();
        $transaction->start();

        // Fetch another transaction, it should fail
        try {
            $connection->startTransaction();
            $this->fail();
        } catch (TransactionError $e) {
        }

        // Fetch another transaction, it should NOT fail
        $t3 = $connection->startTransaction(Transaction::REPEATABLE_READ, true);
        $this->assertSame($t3, $transaction);
        $this->assertTrue($t3->isStarted());

        // Force rollback of the second, ensure previous is stopped too
        $t3->rollback();
        $this->assertFalse($transaction->isStarted());
    }

    /**
     * Internal test for testWeakRefAllowFailOnScopeClose()
     *
     * @param ConnectionInterface $connection
     */
    protected function privateScopeForWeakRef(ConnectionInterface $connection)
    {
        $transaction = $connection->startTransaction();
        $transaction->start();

        // Force fail
        unset($transaction);
    }

    /**
     * Test that when a transaction goes out of scope, it dies and raise an
     * exception if it was not closed: this can only work with the weakref
     * extension enabled
     */
    public function testWeakRefAllowFailOnScopeClose()
    {
        if (!extension_loaded('weakref')) {
            $this->markTestSkipped("this test can only work with the WeakRef extension");
        }

        try {
            $this->privateScopeForWeakRef($this->getConnection());
            $this->fail();
        } catch (TransactionError $e) {
            // Success
        }
    }

    /**
     * Test the savepoint feature
     */
    public function testTransactionSavepoint()
    {
        $connection = $this->getConnection();

        $transaction = $connection->startTransaction();
        $transaction->start();

        $connection
            ->update('transaction_test')
            ->set('bar', 'z')
            ->condition('foo', 1)
            ->execute()
        ;

        $transaction->savepoint('bouyaya');

        $connection
            ->update('transaction_test')
            ->set('bar', 'y')
            ->condition('foo', 2)
            ->execute()
        ;

        $transaction->rollbackToSavepoint('bouyaya');
        $transaction->commit();

        $oneBar = $connection
            ->select('transaction_test')
            ->column('bar')
            ->condition('foo', 1)
            ->execute()
            ->fetchField()
        ;
        // This should have pass since it's before the savepoint
        $this->assertSame('z', $oneBar);

        $twoBar = $connection
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
