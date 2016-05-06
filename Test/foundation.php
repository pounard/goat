<?php
ini_set('zend.assertions', 1);
ini_set('assert.exception', 1);
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);

require_once __DIR__ . '/../vendor/autoload.php';

use Momm\Foundation\SessionBuilder;
use Momm\Foundation\Session\ResultHandler;
use PommProject\Foundation\ConvertedResultIterator;

$builder = new SessionBuilder(['dsn'  => 'mysql://momm:momm@localhost:3306/momm', 'name' => 'momm_test']);

$session = $builder->buildSession();

/*
 * assert basic result handler functionality
 */

$handler = $session->getConnection()->sendQueryWithParameters("select $1 as foo, 2 as bar, from_unixtime($2) as myDate, 'ueiroizuroizruzo', 1", [42, time()]);
assert($handler->countFields() === 5);
assert($handler->getFieldName(4) === 4);
assert($handler->getFieldType('myDate') === 'timestamp');

try {
  $handler->getFieldName(12);
  assert(false);
} catch (\OutOfBoundsException $e) {}

try {
  $handler->getFieldName(-1);
  assert(false);
} catch (\OutOfBoundsException $e) {}

try {
  $handler->getFieldType(-1);
  assert(false);
} catch (\OutOfBoundsException $e) {}

/*
 * do some weird job, but we need to be sure mysql will give the right types
 */

$session->getConnection()->executeAnonymousQuery(<<<EOT
    create temporary table type_test (
        foo integer unsigned,
        bar varchar(255),
        baz datetime
    )
EOT
);

$session->getConnection()->executeAnonymousQuery(<<<EOT
    insert into type_test (foo, bar, baz) values (42, 'cassoulet', '1983-03-22 08:25:00');
EOT
);

/** @var $handler ConvertedResultIterator */
$results = $session->getQueryManager()->query("select * from type_test");

assert(count($results) === 1);

foreach ($results as $result) {
  assert(is_int($result['foo']));
  assert(is_string($result['bar']));
  assert($result['baz'] instanceof \DateTime && '1983-03-22 08:25:00' === $result['baz']->format('Y-m-d H:i:s'));
}

/*
 * and a prepared query for fun
 */

$prepared = $session->getPreparedQuery('select * from type_test');

/** @var $handler ResultHandler */
$handler = $prepared->execute(['foo', 42, 666]);
assert($handler->countFields() === 3);
assert($handler->getFieldName(0) === 'foo');
assert($handler->getFieldName(1) === 'bar');
assert($handler->getFieldName(2) === 'baz');

/*
 * and a simple query
 */

/** @var $handler ConvertedResultIterator */
$results = $session
    ->getQueryManager()
    ->query(
        "select $*::varchar as foo, $*::int4 as bar, $*::timestamp as baz, $*::timestamp as fux",
        ["cassoulet", "12", \DateTime::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00'), \DateTime::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00')]
    )
;

assert(count($results) === 1);

foreach ($results as $result) {
  assert(is_string($result['foo']));
  assert(is_int($result['bar']));
  assert($result['baz'] instanceof \DateTime && '1983-03-22 08:25:00' === $result['baz']->format('Y-m-d H:i:s'));
  assert($result['baz'] instanceof \DateTime && '08:25:00' === $result['baz']->format('H:i:s'));
}
