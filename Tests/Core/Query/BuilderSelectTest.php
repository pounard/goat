<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\ExpressionRaw;
use Goat\Core\Query\Query;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\SqlFormatter;
use Goat\Core\Query\ExpressionColumn;

class BuilderSelectTest extends \PHPUnit_Framework_TestCase
{
    use BuilderTestTrait;

    public function testSimpleQuery()
    {
        $formatter = new SqlFormatter(new NullEscaper());

        $referenceArguments = [12, 3];
        $reference = <<<EOT
select "t".*, "n"."type", count(n.id) as "comment_count"
from "task" as "t"
left outer join "task_note" as "n"
    on (n.task_id = t.id)
where
    "t"."user_id" = $*
    and "t"."deadline" < now()
group
    by "t"."id", "n"."type"
order by
    "n"."type" asc,
    count(n.nid) desc
limit 7 offset 42
having
    count(n.nid) < $*
EOT;
        $countReference = <<<EOT
select count(*) as "count"
from "task" as "t"
left outer join "task_note" as "n"
    on (n.task_id = t.id)
where
    "t"."user_id" = $*
    and "t"."deadline" < now()
group
    by "t"."id", "n"."type"
order by
    "n"."type" asc,
    count(n.nid) desc
having
    count(n.nid) < $*
EOT;

        // Compact way
        $query = new SelectQuery('task', 't');
        $query->column('t.*');
        $query->column('n.type');
        $query->column(new ExpressionRaw('count(n.id)'), 'comment_count');
        // Add and remove a column for fun
        $query->column('some_field', 'some_alias')->removeColumn('some_alias');
        $query->leftJoin('task_note', 'n.task_id = t.id', 'n');
        $query->groupBy('t.id');
        $query->groupBy('n.type');
        $query->orderBy('n.type');
        $query->orderBy(new ExpressionRaw('count(n.nid)'), Query::ORDER_DESC);
        $query->range(7, 42);
        $where = $query->getWhere();
        $where->condition('t.user_id', 12);
        $where->condition('t.deadline', new ExpressionRaw('now()'), '<');
        $having = $query->getHaving();
        $having->expression('count(n.nid) < $*', 3);

        $this->assertSameSql($reference, $formatter->format($query));
        $this->assertSame($referenceArguments, $query->getArguments()->getAll());

        $countQuery = $query->getCountQuery();
        $this->assertSameSql($countReference, $formatter->format($countQuery));
        $this->assertSame($referenceArguments, $countQuery->getArguments()->getAll());

        $clonedQuery = clone $query;
        $this->assertSameSql($reference, $formatter->format($clonedQuery));
        $this->assertSame($referenceArguments, $clonedQuery->getArguments()->getAll());

        // We have to reset the reference because using a more buildish way we
        // do set precise where conditions on join conditions, and field names
        // get escaped
        $reference = <<<EOT
select "t".*, "n"."type", count(n.id) as "comment_count"
from "task" as "t"
left outer join "task_note" as "n"
    on ("n"."task_id" = "t"."id")
where
    "t"."user_id" = $*
    and "t"."deadline" < now()
group
    by "t"."id", "n"."type"
order by
    "n"."type" asc,
    count(n.nid) desc
limit 7 offset 42
having
    count(n.nid) < $*
EOT;
        $countReference = <<<EOT
select count(*) as "count"
from "task" as "t"
left outer join "task_note" as "n"
    on ("n"."task_id" = "t"."id")
where
    "t"."user_id" = $*
    and "t"."deadline" < now()
group
    by "t"."id", "n"."type"
order by
    "n"."type" asc,
    count(n.nid) desc
having
    count(n.nid) < $*
EOT;

        // Builder way
        $query = (new SelectQuery('task', 't'))
            ->column('t.*')
            ->column('n.type')
            ->columnExpression('count(n.id)', 'comment_count')
            ->groupBy('t.id')
            ->groupBy('n.type')
            ->orderBy('n.type')
            ->orderByExpression('count(n.nid)', Query::ORDER_DESC)
            ->range(7, 42)
        ;
        $query
            ->leftJoinWhere('task_note', 'n')
            ->condition('n.task_id', new ExpressionColumn('t.id'))
        ;
        $where = $query->getWhere()
            ->condition('t.user_id', 12)
            ->condition('t.deadline', new ExpressionRaw('now()'), '<')
        ;
        $having = $query->getHaving()
            ->expression('count(n.nid) < $*', 3)
        ;

        $this->assertSameSql($reference, $formatter->format($query));
        $this->assertSame($referenceArguments, $query->getArguments()->getAll());

        $countQuery = $query->getCountQuery();
        $this->assertSameSql($countReference, $formatter->format($countQuery));
        $this->assertSame($referenceArguments, $countQuery->getArguments()->getAll());

        $clonedQuery = clone $query;
        $this->assertSameSql($reference, $formatter->format($clonedQuery));
        $this->assertSame($referenceArguments, $clonedQuery->getArguments()->getAll());

        // Same without alias
        $reference = <<<EOT
select "task".*, "task_note"."type", count(task_note.id) as "comment_count"
from "task"
left outer join "task_note"
    on (task_note.task_id = task.id)
where
    "task"."user_id" = $*
    and task.deadline < now()
group by
    "task"."id", "task_note"."type"
order by
    "task_note"."type" asc,
    count(task_note.nid) desc
limit 7 offset 42
having
    count(task_note.nid) < $*
EOT;
        $countReference = <<<EOT
select count(*) as "count"
from "task"
left outer join "task_note"
    on (task_note.task_id = task.id)
where
    "task"."user_id" = $*
    and task.deadline < now()
group by
    "task"."id", "task_note"."type"
order by
    "task_note"."type" asc,
    count(task_note.nid) desc
having
    count(task_note.nid) < $*
EOT;

        // Most basic way
        $query = (new SelectQuery('task'))
            ->column('task.*')
            ->column('task_note.type')
            ->columnExpression('count(task_note.id)', 'comment_count')
            ->leftJoin('task_note', 'task_note.task_id = task.id', 'task_note')
            ->groupBy('task.id')
            ->groupBy('task_note.type')
            ->orderBy('task_note.type')
            ->orderByExpression('count(task_note.nid)', Query::ORDER_DESC)
            ->range(7, 42)
            ->condition('task.user_id', 12)
            ->expression('task.deadline < now()')
            ->havingExpression('count(task_note.nid) < $*', 3)
        ;

        $this->assertSameSql($reference, $formatter->format($query));
        $this->assertSame($referenceArguments, $query->getArguments()->getAll());

        $countQuery = $query->getCountQuery();
        $this->assertSameSql($countReference, $formatter->format($countQuery));
        $this->assertSame($referenceArguments, $countQuery->getArguments()->getAll());

        $clonedQuery = clone $query;
        $this->assertSameSql($reference, $formatter->format($clonedQuery));
        $this->assertSame($referenceArguments, $clonedQuery->getArguments()->getAll());
    }
}
