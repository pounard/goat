<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Core\Error\QueryError;
use Goat\Core\Transaction\Transaction;
use Goat\Query\SqlFormatterInterface;

class MySQLConnection extends AbstractPDOConnection
{
    /**
     * {@inheritdoc}
     */
    protected function fetchDatabaseInfo() : array
    {
        $result = $this->getPdo()->query("show variables like '%version%';");

        $data = [];
        foreach ($result as $row) {
            list($variable, $value) = $row;;
            $data[$variable] = $value;
        }

        return [
            'name'    => 'MySQL',
            'version' => $data['innodb_version'],
            'arch'    => $data['version_compile_machine'],
            'os'      => $data['version_compile_os'],
            'build'   => $data['version'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEscapeSequences() : array
    {
        return [
            '`',    // Identifier escape character
            '\'',   // String literal escape character
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ) : Transaction
    {
        $ret = new MySQLTransaction($isolationLevel);
        $ret->setConnection($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getCastType(string $type) : string
    {
        // Specific type conversion for MySQL because its CAST() function
        // does not accepts the same datatypes as the one it handles.
        if ('timestamp' === $type) {
            return 'datetime';
        } else if ('int' === substr($type, 0, 3)) {
            return 'signed integer';
        } else if ('float' === substr($type, 0, 5) || 'double' === substr($type, 0, 6)) {
            return 'decimal';
        }

        return $type;
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

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding(string $encoding)
    {
        // Keeping the MySQL-specific client encoding directive to ensure it
        // will work with older MySQL versions. It seems while browsing
        // randomly the documentation that versions prior to 5.5 don't support
        // this, or it's undocumented.
        // https://dev.mysql.com/doc/refman/5.7/en/set-names.html
        $this
            ->getPdo()
            ->query(
                sprintf(
                    "SET character_set_client = %s",
                    $this->escapeLiteral($encoding)
                )
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function createFormatter() : SqlFormatterInterface
    {
        return new MySQLFormatter($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function sendConfiguration(array $configuration)
    {
        $pdo = $this->getPdo();

        foreach ($configuration as $key => $value) {
            $pdo->query(sprintf(
                "SET %s = %s",
                $this->escapeIdentifier($key),
                $this->escapeLiteral($value)
            ));
        }

        return $this;
    }
}
