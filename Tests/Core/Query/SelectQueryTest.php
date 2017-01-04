<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\RawStatement;

class SelectQueryTest extends \PHPUnit_Framework_TestCase
{
    use SqlTestTrait;

    public function testSimpleQuery()
    {
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

        // Compact way
        $query = new SelectQuery('task', 't');
        $query->field('t.*');
        $query->field('n.type');
        $query->field('count(n.id)', 'comment_count');
        // Add and remove a field for fun
        // $query->field('some_field', 'some_alias')->removeField('some_field', 'some_alias');
        $query->leftJoin('task_note', 'n.task_id = t.id', 'n');
        $query->groupBy('t.id');
        $query->groupBy('n.type');
        $query->orderBy('n.type');
        $query->orderBy('count(n.nid)', SelectQuery::ORDER_DESC);
        $query->range(7, 42);
        $where = $query->where();
        $where->condition('t.user_id', 12);
        $where->condition('t.deadline', $where->raw('now()'), '<');
        $having = $query->having();
        $having->statement('count(n.nid) < $*', 3);

        $this->assertSameSql($reference, $query->format());
        $this->assertSame($referenceArguments, $query->getArguments());

        // Builder way
        $query = (new SelectQuery('task', 't'))
            ->field('t.*')
            ->field('n.type')
            ->field('count(n.id)', 'comment_count')
            ->groupBy('t.id')
            ->groupBy('n.type')
            ->orderBy('n.type')
            ->orderBy('count(n.nid)', SelectQuery::ORDER_DESC)
            ->range(7, 42)
        ;
        $query
            ->leftJoin('task_note', null, 'n')
            ->condition('n.task_id', new RawStatement('t.id'))
        ;
        $where = $query->where()
            ->condition('t.user_id', 12)
            ->condition('t.deadline', $where->raw('now()'), '<')
        ;
        $having = $query->having()
            ->statement('count(n.nid) < $*', 3)
        ;

        $this->assertSameSql($reference, $query->format());
        $this->assertSame($referenceArguments, $query->getArguments());

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

        // Most basic way
        $query = new SelectQuery('task');
        $query->field('task.*');
        $query->field('task_note.type');
        $query->field('count(task_note.id)', 'comment_count');
        $query->leftJoin('task_note', 'task_note.task_id = task.id', 'task_note');
        $query->groupBy('task.id');
        $query->groupBy('task_note.type');
        $query->orderBy('task_note.type');
        $query->orderBy('count(task_note.nid)', SelectQuery::ORDER_DESC);
        $query->range(7, 42);
        $where = $query->where();
        $where->condition('task.user_id', 12);
        $where->condition('task.deadline', $where->raw('now()'), '<');
        $having = $query->having();
        $having->statement('count(task_note.nid) < $*', 3);

        $this->assertSameSql($reference, $query->format());

        $this->assertSame($referenceArguments, $query->getArguments());
    }
}