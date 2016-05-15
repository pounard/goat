<?php

namespace Momm\Tests\Core\Query;

use Momm\Core\Query\Where;

class WhereTest extends \PHPUnit_Framework_TestCase
{
    public function testWhere()
    {
        $where = (new Where())
            // Simple '<>' operator
            ->condition('foo', 'bar', Where::NOT_EQUAL)
            // Simple '=' operator
            ->condition('foo', 'foo')
            // Will turn into a 'in' operator
            ->condition('baz', [1, 2, 3])
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
            ->condition('tabouert', 'cassoulet')
            ->open(Where::OR_STATEMENT)
                ->condition('test', 1)
                ->condition('other', ['this', 'is', 'an array'])
            ->close()
        ;

        $expected = <<<EOT
            foo <> $*
            and foo = $*
            and baz in ($*, $*, $*)
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
                b >= $*
                c < $*
                d <= $*
            )
            and roger is null
            and tabouert = $*
            and (
                test = $*
                or other in ($*, $*, $*)
            )"
EOT;

        // Remove all whitespaces, and lowercase everything to ensure our
        // generation cruft don't change trigger false negatives
        $sql = (string)$where;
        $values = $where->getArguments();
        $expected = trim(strtolower(preg_replace('@\s+@', ' ', $sql)));
        $actual = trim(strtolower(preg_replace('@\s+@', ' ', $sql)));
        $this->assertSame($expected, $actual);

        // And now the exact same where, using convenience methods
        $where = (new Where())
            ->isNotEqual('foo', 'bar')
            ->isEqual('foo', 'foo')
            ->isEqual('baz', [1, 2, 3])
            ->isNotIn('baz', [4, 5, 6])
            ->open(Where::OR_STATEMENT)
                // Custom operator cannot have a convenience method
                ->condition('theWorld', 'enough', 'is not')
                // Statement is statement, no surprises
                ->statement('count(theWorld) = $*::int4', [1])
                ->open()
                    ->isEqual('1', 0)
                    ->isEqual('2 * 2', 5)
                ->close()
            ->close()
            ->open()
                ->isBetween('beta', 37, 42)
                ->isNotBetween('gamma', 123, 234)
            ->close()
            ->open()
                ->isGreater('a', -66)
                ->isGreaterOrEqual('b', -67)
                ->isLess('c', -68)
                ->isLessOrEqual('d', -69)
            ->close()
            ->isNull('roger')
            ->isEqual('tabouert', 'cassoulet')
            ->open(Where::OR_STATEMENT)
                ->isEqual('test', 1)
                ->isIn('other', ['this', 'is', 'an array'])
            ->close()
        ;

        // Expected is the exact same
        $sql = (string)$where;
        $values = $where->getArguments();
        $actual = trim(strtolower(preg_replace('@\s+@', ' ', $sql)));
        $this->assertSame($expected, $actual);
    }
}
