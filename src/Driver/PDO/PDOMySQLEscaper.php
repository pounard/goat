<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Error\QueryError;

/**
 * MySQL SQL query formatter
 */
class PDOMySQLEscaper extends AbstractPDOEscaper
{
    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences() : array
    {
        return [
            '`',    // Identifier escape character
            '\'',   // String literal escape character
        ];
    }

    /**
     * Ensures that the identifier does not contain any ? sign, this is due to
     * the fact that PDO has a real bug out there: where it does gracefull
     * detects that ? in string literals are not parameters, it fails when
     * it ? is in an MySQL or PostgreSQL identifier literal, as well as sometime
     * it fails when it is in a PostgreSQL string constant (enclosed with $$).
     *
     * What this function does is simply throwing exceptions when there is any
     * number of ? sign in the identifier.
     *
     * For more documentation, you may read this informative Stack Overflow
     * thread, where the question is raised about ? in identifiers:
     *   https://stackoverflow.com/q/12092907
     *
     * Also note that there's an actual PDO bug opened, but I guess it will
     * never be fixed, it's too much of an edge case:
     *   https://bugs.php.net/bug.php?id=71628
     *
     * And yet I have absolutely no idea why, but using the pdo_pgsql driver
     * it does work gracefully, I guess this is because it considers that
     * strings enclosed by using double quote (") are string literals, and
     * this is the right way of escaping identifiers for PosgresSQL so this
     * passes silently and works gracefully.
     *
     * @param string $string
     */
    private function checkIdentifier(string $string)
    {
        if (false !== strpos($string, '?')) {
            throw new QueryError("PDO can't support '?' sign within identifiers, please read https://stackoverflow.com/q/12092907");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string) : string
    {
        $this->checkIdentifier($string);

        return '`' . str_replace('`', '``', $string) . '`';
    }
}
