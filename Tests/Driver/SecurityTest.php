<?php

namespace Goat\Tests\Driver;

use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Error\GoatError;
use Goat\Core\Query\ExpressionColumn;
use Goat\Core\Query\ExpressionRelation;
use Goat\Tests\DriverTestCase;

/**
 * Enfore strong security injection tests.
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
    protected function createTestSchema(ConnectionInterface $connection)
    {
        $connection->query("
            create temporary table some_table (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp default now()
            )
        ");
    }

    /**
     * Test basic result iterator usage
     *
     * @dataProvider driverDataSource
     */
    public function testCreateTableAndColumn($driver, $class)
    {
        $connection = $this->createConnection($driver, $class);

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
        foreach ($this->getStrings() as $veryBadString) {
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
            $veryBadString = 'a' . $veryBadString;

            try {
                $sql = sprintf(
                    "create temporary table %s (%s varchar(1024))",
                    $connection->escapeIdentifier($veryBadString),
                    $connection->escapeIdentifier($veryBadString)
                );

                $connection->query($sql);

                // We are going to test with whatever passed.
                $result = $connection
                    ->insertValues(ExpressionRelation::escape($veryBadString))
                    ->columns([$veryBadString])
                    ->values(['some_text'])
                    ->execute()
                ;
                $this->assertSame(1, $result->countRows());

                $result = $connection
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

                /** @var \Throwable $previous */
                $previous = $e;
                $isValid = false;

                do {
                    foreach ($allowedErrors as $partialMessage) {
                        if (false !== stripos($previous->getMessage(), $partialMessage)) {
                            $isValid = true;
                            break 2;
                        }
                    }
                } while ($previous = $previous->getPrevious());

                if (!$isValid) {
                    throw new GoatError(sprintf("error with string %s", escapeshellarg($veryBadString)), $e->getCode(), $e);
                }
            }
        }
    }
}
