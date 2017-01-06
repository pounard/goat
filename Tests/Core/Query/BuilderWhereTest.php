<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Where;

class BuilderWhereTest extends \PHPUnit_Framework_TestCase
{
    use BuilderTestTrait;

    public function testWhere()
    {
        $formatter = $this->createStandardSQLFormatter();

        $where = (new Where())
            // Simple '<>' operator
            ->condition('foo', 'bar', Where::NOT_EQUAL)
            // Simple '=' operator
            ->condition('foo', 'foo')
            // Will turn into a 'in' operator
            ->condition('baz', [1, 2, 3])
            // Between and not between operators
            ->condition('range_a', [12, 24], Where::BETWEEN)
            ->condition('range_b', [48, 96], Where::NOT_BETWEEN)
            // Expliciti 'not in' operator
            ->condition('baz', [4, 5, 6], Where::NOT_IN)
            // We will build something here
            ->open(Where::OR_STATEMENT)
                // Arbitrary operator, should work too
                ->condition('theWorld', 'enough', 'is not')
                ->statement('count(theWorld) = $*::int4', [1])
                // Parenthesis inside parenthesis is recursive
                // By the way, default is AND_STATEMENT
                ->open()
                    ->condition('1', 0)
                    ->condition('2 * 2', 5)
                ->close()
            // Close parenthesis
            ->close()
            ->open()
                // Between and not between
                ->condition('beta', [37, 42], Where::BETWEEN)
                ->condition('gamma', [123, 234], Where::NOT_BETWEEN)
            ->close()
            ->open()
                // Comparisons
                ->condition('a', -66, Where::GREATER)
                ->condition('b', -67, Where::GREATER_OR_EQUAL)
                ->condition('c', -68, Where::LESS)
                ->condition('d', -69, Where::LESS_OR_EQUAL)
            ->close()
            ->isNull('roger')
            ->condition('tabouret', 'cassoulet')
            ->open(Where::OR_STATEMENT)
                ->condition('test', 1)
                ->condition('other', ['this', 'is', 'an array'])
            ->close()
        ;

        $reference = <<<EOT
foo <> $*
and foo = $*
and baz in ($*, $*, $*)
and range_a between $* and $*
and range_b not between $* and $*
and baz not in ($*, $*, $*)
and (
    theWorld is not $*
    or count(theWorld) = $*::int4
    or (
        1 = $*
        and 2 * 2 = $*
    )
)
and (
    beta between $* and $*
    and gamma not between $* and $*
)
and (
    a > $*
    and b >= $*
    and c < $*
    and d <= $*
)
and roger is null
and tabouret = $*
and (
    test = $*
    or other in ($*, $*, $*)
)
EOT;

        $this->assertSameSql($reference, $formatter->formatWhere($where));

        // And now the exact same where, using convenience methods
        $where = (new Where())
            ->isNotEqual('foo', 'bar')
            ->isEqual('foo', 'foo')
            ->isEqual('baz', [1, 2, 3])
            ->isBetween('range_a', 12, 24)
            ->isNotBetween('range_b', 48, 96)
            ->isNotIn('baz', [4, 5, 6])
            ->orStatement()
                // Custom operator cannot have a convenience method
                ->condition('theWorld', 'enough', 'is not')
                // Statement is statement, yield no surprises
                ->statement('count(theWorld) = $*::int4', [1])
                ->andStatement()
                    ->isEqual('1', 0)
                    ->isEqual('2 * 2', 5)
                ->end()
            ->end()
            ->andStatement()
                ->isBetween('beta', 37, 42)
                ->isNotBetween('gamma', 123, 234)
            ->end()
            ->andStatement()
                ->isGreater('a', -66)
                ->isGreaterOrEqual('b', -67)
                ->isLess('c', -68)
                ->isLessOrEqual('d', -69)
            ->end()
            ->isNull('roger')
            ->isEqual('tabouret', 'cassoulet')
            ->orStatement()
                ->isEqual('test', 1)
                ->isIn('other', ['this', 'is', 'an array'])
            ->end()
        ;

        // Expected is the exact same
        $this->assertSameSql($reference, $formatter->formatWhere($where));
    }

    public function testWhereWhenEmpty()
    {
        $formatter = $this->createStandardSQLFormatter();

        $where = (new Where());

        // Where is empty
        $this->assertTrue($where->isEmpty());
        $this->assertSameSql("1", $formatter->formatWhere($where));

        // Where is not empty anymore
        $where->isNotNull('a');
        $this->assertFalse($where->isEmpty());
        $this->assertSameSql("a is not null", $formatter->formatWhere($where));

        // Statement is empty
        $statement = $where->andStatement();
        $this->assertTrue($statement->isEmpty());
        $this->assertSameSql("1", $formatter->formatWhere($statement));

        // Statement is ignored, because empty
        $this->assertSameSql("a is not null", $formatter->formatWhere($where));
    }
}
