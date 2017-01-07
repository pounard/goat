<?php

namespace Goat\Tests\Core\Transaction;

use Goat\Tests\ConnectionAwareTestTrait;
use Goat\Core\Error\TransactionFailedError;
use Goat\Core\Error\GoatError;

abstract class AbstractTransactionTest extends \PHPUnit_Framework_TestCase
{
    use ConnectionAwareTestTrait;

    /**
     * Create test case table
     */
    protected function createTestTable()
    {
        $connection = $this->getConnection();
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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->createTestTable();
    }

    /**
     * Normal working transaction
     */
    public function testTransaction()
    {
        $connection = $this->getConnection();

        $transaction = $connection->transaction();
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
            ->transaction()
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
            ->transaction()
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
            ->transaction()
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

        $transaction = $connection->transaction();
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
     * Test the savepoint feature
     */
    public function testTransactionSavepoint()
    {
        $connection = $this->getConnection();
    }

    /**
     * Test that left open transactions allways rollback and raise exceptions
     */
    public function testDestructRollbacks()
    {
        $connection = $this->getConnection();
    }
}
