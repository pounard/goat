<?php
ini_set('zend.assertions', 1);
ini_set('assert.exception', 1);
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);

require_once __DIR__ . '/../vendor/autoload.php';

use Momm\ModelManager\Session;
use Momm\ModelManager\SessionBuilder;
use Momm\Test\Model\Task;
use Momm\Test\Model\TaskModel;

$builder = new SessionBuilder(['dsn'  => 'mysql://momm:momm@localhost:3306/momm', 'name' => 'momm_test']);

/** @var $session Session */
$session = $builder->buildSession();

$session->getConnection()->executeAnonymousQuery(<<<EOT
    create temporary table task (
        id serial auto_increment primary key,
        is_public tinyint(1) not null default 0,
        ts_created timestamp not null default current_timestamp,
        ts_deadline timestamp,
        user_id integer unsigned not null default 0,
        user_name varchar(128) default 'Anonymous',
        description text not null default ''
    );
EOT
);

/** @var $model TaskModel */
$model = $session->getModel(TaskModel::class);

/** @var $task Task */
$task = $model->findByPK(['id' => 1]);
assert(null === $task);

$date = new \DateTime('now +6 day');
$task = $model->createAndSave([
    'is_public' => 1,
    'ts_deadline' => $date,
    'user_name' => 'Poncho Vire',
    'description' => 'Roger!',
]);

assert($task instanceof Task);
assert($task->is_public === 1);
assert($task->user_name === 'Poncho Vire');
assert($task->description === 'Roger!');
assert($task->ts_deadline instanceof \DateTime && $task->ts_deadline == $date);
assert($task->ts_created instanceof \DateTime && $task->ts_created == $date);
