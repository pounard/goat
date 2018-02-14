<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Error\QueryError;
use Goat\Query\Writer\EscaperBase;

/**
 * PDO based SQL escaper
 */
abstract class AbstractPDOEscaper extends EscaperBase
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get PDO instance, connect if not connected
     *
     * @return \PDO
     */
    protected function getPdo() : \PDO
    {
        return $this->pdo;
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
    protected function checkIdentifier(string $string)
    {
        if (false !== strpos($string, '?')) {
            throw new QueryError("PDO can't support '?' sign within identifiers, please read https://stackoverflow.com/q/12092907");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral(string $string) : string
    {
        return $this->getPdo()->quote($string, \PDO::PARAM_STR);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLike(string $string) : string
    {
        return addcslashes($string, '\%_');
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word) : string
    {
        return $this->getPdo()->quote($word /*, \PDO::PARAM_LOB */);
    }

    /**
     * {@inheritdoc}
     */
    public function unescapeBlob($resource) : ?string
    {
        // I have no idea why, but all of the sudden, PDO pgsql driver started
        // to send resources instead of data...
        if (is_resource($resource)) {
            return stream_get_contents($resource);
        }
        return $resource;
    }
}
