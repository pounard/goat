<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Driver\MySQL\MySQLTransaction;
use Goat\Query\Driver\PDOMySQL5Formatter;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\Transaction;

class PDOMySQLConnection extends AbstractPDOConnection
{
    /**
     * {@inheritdoc}
     */
    protected function createFormatter() : FormatterInterface
    {
        return new PDOMySQL5Formatter($this->getEscaper());
    }

    /**
     * {@inheritdoc}
     */
    protected function createEscaper() : EscaperInterface
    {
        return new PDOMySQLEscaper($this->getPdo());
    }

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
    protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ) : Transaction
    {
        $ret = new MySQLTransaction($isolationLevel);
        $ret->setRunner($this);

        return $ret;
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
                    $this->getEscaper()->escapeLiteral($encoding)
                )
            )
        ;
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
                $this->getEscaper()->escapeIdentifier($key),
                $this->getEscaper()->escapeLiteral($value)
            ));
        }

        return $this;
    }
}
