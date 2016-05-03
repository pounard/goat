<?php
ini_set('zend.assertions', 1);
ini_set('assert.exception', 1);
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);

require_once __DIR__ . '/../vendor/autoload.php';

use Momm\Foundation\SessionBuilder;

$builder = new SessionBuilder(['dsn'  => 'mysql://momm:momm@localhost:3306/momm', 'name' => 'momm_test']);

$session = $builder->buildSession();

/*
 * Assert basic result handler functionality
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
