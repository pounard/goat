<?php

namespace Goat\Benchmark;

use Goat\Core\Client\EscaperInterface;
use Goat\Core\Query\Query;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\SqlFormatter;
use Goat\Core\Query\SqlFormatterInterface;
use Goat\Tests\Core\Query\NullEscaper;

/**
 * Benchmark SQL formatter, sprinf()/implode vs string concat.
 *
 * @BeforeMethods({"setUp"})
 */
class SqlFormatterBenchmark
{
    /**
     * @var SqlFormatterInterface
     */
    private $concatSqlFormatter;

    /**
     * @var SqlFormatterInterface
     */
    private $normalSqlFormatter;

    /**
     * @var EscaperInterface
     */
    private $escaper;

    /**
     * @var Query
     */
    private $selectQuery;

    public function setUp()
    {
        $this->escaper = new NullEscaper();
        $this->concatSqlFormatter = new SqlFormatterConcat($this->escaper);
        $this->normalSqlFormatter = new SqlFormatter($this->escaper);

        // Create a select query
        $this->selectQuery = (new SelectQuery('task', 't'))
            ->column('t.*')
            ->column('n.type')
            ->column('u.name', 'user_name')
            ->columnExpression('count(n.id)', 'comment_count')
            ->leftJoin('task_note', 'n.task_id = t.id', 'n')
            ->innerJoin('users', 'u.id = t.id_user', 'u')
            ->groupBy('t.id')
            ->groupBy('n.type')
            ->orderBy('n.type')
            ->orderByExpression('count(n.nid)', Query::ORDER_DESC)
            ->range(7, 42)
            ->condition('t.user_id', 12)
            ->expression('t.deadline < now()')
            ->havingExpression('count(n.nid) < $*', 3)
        ;
    }

    /**
     * @Revs(100)
     * @Iterations(200)
     */
    public function benchNormalSelect()
    {
        $this->normalSqlFormatter->format($this->selectQuery);
    }

    /**
     * @Revs(100)
     * @Iterations(200)
     */
    public function benchConcatSelect()
    {
        $this->concatSqlFormatter->format($this->selectQuery);
    }
}
