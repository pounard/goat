<?php

declare(strict_types=1);

namespace Goat\Tests\Driver;

use Goat\Driver\DriverInterface;
use Goat\Error\GoatError;
use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRelation;
use Goat\Query\ExpressionValue;
use Goat\Tests\DriverTestCase;

/**
 * Enfore strong security injection tests.
 *
 * Due to the enormous amount of strings, this is the longest test of all
 * nevertheless, this is a small price to pay to have a secure API so nothing
 * will probably ever be done to make it faster.
 *
 * Injected strings from the security-strings.json comes from:
 *   https://github.com/minimaxir/big-list-of-naughty-strings
 *
 * All credits to their authors.
 */
class SecurityTest extends DriverTestCase
{
    /**
     * @var string[]
     */
    private $strings;

    /**
     * Get naughty string
     */
    private function getStrings()
    {
        if (null === $this->strings) {
            $this->strings = json_decode(file_get_contents(__DIR__ . '/security-strings.json'), true);
        }

        return $this->strings;
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(DriverInterface $driver)
    {
        $driver->query("
            create temporary table users (
                id serial primary key
            )
        ");
        $driver->query("
            create temporary table some_table (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp default now()
            )
        ");
    }

    /**
     * Handle driver exceptions, some are acceptable, some other not
     *
     * @param \Throwable $e
     * @param string $veryBadString
     * @param int $index
     * @param string[] $allowedErrors
     *
     * @throws GoatError
     */
    private function handleException(\Throwable $e, string $veryBadString, $index, array $allowedErrors = [])
    {
        $previous = $e;
        $isValid = false;

        if ($allowedErrors) {
            do {
                foreach ($allowedErrors as $partialMessage) {
                    if (false !== stripos($previous->getMessage(), $partialMessage)) {
                        $isValid = true;
                        break 2;
                    }
                }
            } while ($previous = $previous->getPrevious());
        }

        if (!$isValid) {
            throw new GoatError(sprintf("error with string %d: %s", $index, escapeshellcmd($veryBadString)), $e->getCode(), $e);
        }
    }

    /**
     * Test parameter injection testing
     *
     * @dataProvider driverDataSource
     */
    public function testParameterInjection($driverName, $class)
    {
        $this->markTestSkipped("I AM TO SLOW");

        $driver = $this->createDriver($driverName, $class);
        $stringSet  = $this->getStrings();

        // Those are errors, but valid errors, the SQL backend detected invalid
        // strings and does not allows them, that's exactly what we are looking
        // for doing this test
        $allowedErrors = [
            'Illegal mix of collations',
        ];

        // MySQL, sad MySQL, does not uses a case sensitive collation
        // per default, in most environments those tests would fail.
        // In order to fix that, we do have to reduce the test set.
        if (false !== stripos('mysql', $driver->getDatabaseName())) {
            $done = [];
            foreach ($stringSet as $index => $veryBadString) {

                // @todo
                //   MySQL does not seem to like empty strings and various
                //   whitespace caracters, ideally, I should deal with those
                //   myself instead of dropping the test; BUT! PDO don't let
                //   them work either, so I guess it's not my problem.
                if ("" === $veryBadString) {
                    unset($stringSet[$index]);
                    continue;
                }

                // Remove case insentive duplicates
                $lowered = mb_strtolower($veryBadString);
                if (isset($done[$lowered])) {
                    unset($stringSet[$index]);
                    continue;
                }

                $done[$lowered] = true;
            }
        }

        // Massive bulk insert
        $insert = $driver->insertValues('some_table')->columns(['foo', 'bar']);
        foreach ($stringSet as $index => $veryBadString) {
            $insert->values([$index, $veryBadString]);
        }
        $insert->execute();

        foreach ($stringSet as $index => $veryBadString) {
            try {
                $row = $driver
                    ->select('some_table')
                    ->expression('bar = $*', [$veryBadString])
                    ->execute()
                    ->fetch()
                ;
                $this->assertSame($index, $row['foo']);
                $this->assertSame($veryBadString, $row['bar']);

                $row = $driver
                    ->select('some_table')
                    ->condition('bar', $veryBadString)
                    ->execute()
                    ->fetch()
                ;
                $this->assertSame($index, $row['foo']);
                $this->assertSame($veryBadString, $row['bar']);

                $row = $driver
                    ->select('some_table')
                    ->condition('bar', ':bad')
                    ->execute(['bad' => $veryBadString])
                    ->fetch()
                ;
                $this->assertSame($index, $row['foo']);
                $this->assertSame($veryBadString, $row['bar']);

                $row = $driver
                    ->select('some_table')
                    ->condition('bar', new ExpressionValue($veryBadString))
                    ->execute()
                    ->fetch()
                ;
                $this->assertSame($index, $row['foo']);
                $this->assertSame($veryBadString, $row['bar']);

                // @todo missing LIKE testing

                // Ensures that the user table still exists
                $result = $driver->select('users')->execute();
                $this->assertSame(0, $result->countRows());

            } catch (\Exception $e) {
                $this->handleException($e, $veryBadString, $index, $allowedErrors);
            }
        }
    }

    /**
     * Test basic result iterator usage
     *
     * @dataProvider driverDataSource
     */
    public function testCreateTableAndColumn($driverName, $class)
    {
        $this->markTestSkipped("I AM TO SLOW");

        $driver = $this->createDriver($driverName, $class);

        // Those are errors, but valid errors, the SQL backend detected invalid
        // strings and does not allows them, that's exactly what we are looking
        // for doing this test
        $allowedErrors = [
            'incorrect table name',
            'invalid utf8',
            'incomplete utf8',
            'invalid multibyte',
            'incomplete multibyte',
            'character not in repertoire',
            // See \Goat\Driver\PDO\AbstractPDOConnection::checkIdentifier() for details
            'https://stackoverflow.com/q/12092907',
        ];

        $done = [];
        foreach ($this->getStrings() as $index => $veryBadString) {
            if ("" === $veryBadString) {
                continue;
            }

            // Default maximum identifier size for PostgreSQL is 64, MySQL
            // is probably more or less the same
            if (64 <= strlen($veryBadString)) {
                $veryBadString = substr($veryBadString, 0, 63);
            }

            // Because we did rewrite a bit the strings, we need to ensure we
            // didn't do the same thing twice.
            if (isset($done[$veryBadString])) {
                continue;
            }
            $done[$veryBadString] = true;

            // Prefix to be good with standard.
            $veryBadString = $veryBadString;

            try {
                $sql = sprintf(
                    "create temporary table %s (%s varchar(1024))",
                    $driver->getEscaper()->escapeIdentifier($veryBadString),
                    $driver->getEscaper()->escapeIdentifier($veryBadString)
                );

                $driver->query($sql);

                // We are going to test with whatever passed.
                $result = $driver
                    ->insertValues(ExpressionRelation::escape($veryBadString))
                    ->columns([$veryBadString])
                    ->values(['some_text'])
                    ->execute()
                ;
                $this->assertSame(1, $result->countRows());

                $result = $driver
                    ->select(ExpressionRelation::escape($veryBadString))
                    ->groupBy(ExpressionColumn::escape($veryBadString))
                    ->orderBy(ExpressionColumn::escape($veryBadString))
                    ->execute()
                ;

                $this->assertSame(1, $result->count());
                $this->assertSame(1, $result->countColumns());
                foreach ($result as $row) {
                    $this->assertSame('some_text', reset($row));
                }

            } catch (\Exception $e) {
                $this->handleException($e, $veryBadString, $index, $allowedErrors);
            }
        }
    }
}
