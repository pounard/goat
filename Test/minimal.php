<?php
ini_set('zend.assertions', 1);
ini_set('assert.exception', 1);
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);

require_once __DIR__ . '/../vendor/autoload.php';

use Momm\Foundation\SessionBuilder;
use Momm\Foundation\Session\ResultHandler;

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
 * and a prepared query for fun
 */

$prepared = $session->getPreparedQuery('select $*::varchar as foo, $*::int4 as bar, $*::int4 as baz');

/** @var $handler ResultHandler */
$handler = $prepared->execute(['foo', 42, 666]);
assert($handler->countFields() === 3);
assert($handler->getFieldName(0) === 'foo');
assert($handler->getFieldName(1) === 'bar');
assert($handler->getFieldName(2) === 'baz');

