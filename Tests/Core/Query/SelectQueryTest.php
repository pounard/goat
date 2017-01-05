<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\Query;
use Goat\Core\Query\RawStatement;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\SqlFormatter;

class SelectQueryTest extends \PHPUnit_Framework_TestCase
{
    use SqlTestTrait;

    public function testSimpleQuery()
    {
        $formatter = new SqlFormatter(new NullEscaper());

        $referenceArguments = [12, 3];
        $reference = <<<EOT
select t.*, n.type as type, count(n.id) as comment_count
from task t
left outer join task_note n
    on (n.task_id = t.id)
where
    t.user_id = $*
    and t.deadline < now()
group
    by t.id, n.type
order by
    n.type asc,
    count(n.nid) desc
limit 7 offset 42
having
    count(n.nid) < $*
EOT;
        $countReference = <<<EOT
select count(*) as count
from task t
left outer join task_note n
    on (n.task_id = t.id)
where
    t.user_id = $*
    and t.deadline < now()
group
    by t.id, n.type
order by
    n.type asc,
    count(n.nid) desc
having
    count(n.nid) < $*
EOT;

        // Compact way
        $query = new SelectQuery('task', 't');
        $query->column('t.*');
        $query->column('n.type');
        $query->column('count(n.id)', 'comment_count');
        // Add and remove a column for fun
        $query->column('some_field', 'some_alias')->removeColumn('some_alias');
        $query->leftJoin('task_note', 'n.task_id = t.id', 'n');
        $query->groupBy('t.id');
        $query->groupBy('n.type');
        $query->orderBy('n.type');
        $query->orderBy('count(n.nid)', Query::ORDER_DESC);
        $query->range(7, 42);
        $where = $query->where();
        $where->condition('t.user_id', 12);
        $where->condition('t.deadline', $where->raw('now()'), '<');
        $having = $query->having();
        $having->statement('count(n.nid) < $*', 3);

        $this->assertSameSql($reference, $formatter->format($query));
        $this->assertSame($referenceArguments, $query->getArguments());

        $countQuery = $query->getCountQuery();
        $this->assertSameSql($countReference, $formatter->format($countQuery));
        $this->assertSame($referenceArguments, $countQuery->getArguments());

        $clonedQuery = clone $query;
        $this->assertSameSql($reference, $formatter->format($clonedQuery));
        $this->assertSame($referenceArguments, $clonedQuery->getArguments());

        // Builder way
        $query = (new SelectQuery('task', 't'))
            ->column('t.*')
            ->column('n.type')
            ->column('count(n.id)', 'comment_count')
            ->groupBy('t.id')
            ->groupBy('n.type')
            ->orderBy('n.type')
            ->orderBy('count(n.nid)', Query::ORDER_DESC)
            ->range(7, 42)
        ;
        $query
            ->leftJoinWhere('task_note', 'n')
            ->condition('n.task_id', new RawStatement('t.id'))
        ;
        $where = $query->where()
            ->condition('t.user_id', 12)
            ->condition('t.deadline', $where->raw('now()'), '<')
        ;
        $having = $query->having()
            ->statement('count(n.nid) < $*', 3)
        ;

        $this->assertSameSql($reference, $formatter->format($query));
        $this->assertSame($referenceArguments, $query->getArguments());

        $countQuery = $query->getCountQuery();
        $this->assertSameSql($countReference, $formatter->format($countQuery));
        $this->assertSame($referenceArguments, $countQuery->getArguments());

        $clonedQuery = clone $query;
        $this->assertSameSql($reference, $formatter->format($clonedQuery));
        $this->assertSame($referenceArguments, $clonedQuery->getArguments());

        // Same without alias
        $reference = <<<EOT
select task.*, task_note.type as type, count(task_note.id) as comment_count
from task task
left outer join task_note task_note
    on (task_note.task_id = task.id)
where
    task.user_id = $*
    and task.deadline < now()
group
    by task.id, task_note.type
order by
    task_note.type asc,
    count(task_note.nid) desc
limit 7 offset 42
having
    count(task_note.nid) < $*
EOT;
        $countReference = <<<EOT
select count(*) as count
from task task
left outer join task_note task_note
    on (task_note.task_id = task.id)
where
    task.user_id = $*
    and task.deadline < now()
group
    by task.id, task_note.type
order by
    task_note.type asc,
    count(task_note.nid) desc
having
    count(task_note.nid) < $*
EOT;

        // Most basic way
        $query = (new SelectQuery('task'))
            ->column('task.*')
            ->column('task_note.type')
            ->column('count(task_note.id)', 'comment_count')
            ->leftJoin('task_note', 'task_note.task_id = task.id', 'task_note')
            ->groupBy('task.id')
            ->groupBy('task_note.type')
            ->orderBy('task_note.type')
            ->orderBy('count(task_note.nid)', Query::ORDER_DESC)
            ->range(7, 42)
            ->condition('task.user_id', 12)
            ->statement('task.deadline < now()')
            ->havingStatement('count(task_note.nid) < $*', 3)
        ;

        $this->assertSameSql($reference, $formatter->format($query));
        $this->assertSame($referenceArguments, $query->getArguments());

        $countQuery = $query->getCountQuery();
        $this->assertSameSql($countReference, $formatter->format($countQuery));
        $this->assertSame($referenceArguments, $countQuery->getArguments());

        $clonedQuery = clone $query;
        $this->assertSameSql($reference, $formatter->format($clonedQuery));
        $this->assertSame($referenceArguments, $clonedQuery->getArguments());
    }
}
